<?php

/**
 * Table_Admin
 *
 * This class has been auto-generated by the Doctrine ORM Framework
 */
class Table_Admin extends Table_User
{

  /**
   * Returns an instance of this class.
   *
   * @return Table_Admin
   */
  public static function getInstance() {
    return Doctrine_Core::getTable('Admin');
  }

  /**
   * Logs an administrator in
   * @param string $username Username
   * @param string $password Password
   * @return array
   */
  public function login($username, $password) {
    $encrypted_password = Admin::encryptPassword($password);
    $adapter = new Auth_Adapter_Doctrine('Admin', 'username', 'password');
    $adapter->setIdentity($username);
    $adapter->setCredential($encrypted_password);
    $q = $adapter->getDoctrineQuery();
    $q->where('active = true');
    $auth = Zend_Auth::getInstance();
    $result = $auth->authenticate($adapter);
    if (!$result->isValid()) {
      $auth->clearIdentity();
      throw new Validate_Exception('Username or password incorrect.');
    }
    $userinfo = $adapter->getResultRowObject(null, array('password', 'token', 'password_key', 'active'));
    $auth->getStorage()->write($userinfo);
    // Update Last Login
    $q = Doctrine_Query::create();
    $q->update('Admin');
    $q->set('last_login', '?', date('Y-m-d H:i:s'));
    $q->set('token', 'NULL');
    $q->set('password_key', 'NULL');
    $q->set('token_date', 'NULL');
    $q->where('id = ?', $userinfo->id);
    $q->execute();
    unset($q);
    // return identity
    return $userinfo;
  }

  /**
   * Returns the profile for a given admin
   */
  public function getProfile($id) {
    $q = Doctrine_Query::create();
    $q->select('id, username, email, firstname, lastname, active, accesslevel, signup_date, last_login');
    $q->from('Admin');
    $q->where('id = ?', $id);
    $q->setHydrationMode(Doctrine::HYDRATE_ARRAY);
    return $q->fetchOne();
  }

  /**
   * Updates an administrator profile
   * @param int $id
   * @param SimpleCart_Form $form
   */
  public function updateProfile($id, array $data) {
    $q = Doctrine_Query::create();
    $q->update('Admin');
    foreach ($data as $key => $value) {
      $q->set($key, '?', array($value));
    }
    $q->where('id = ?', $id);
    $q->execute();
  }

  public function changePassword($id, Form_ChangePassword $form) {
    $data = $form->getValues();
    $encrypted_password = Admin::encryptPassword($data['old_password']);
    // Make sure old password is correct
    $q = Doctrine_Query::create();
    $q->select('id');
    $q->from('Admin');
    $q->where('id = ?', $id);
    $q->andWhere('password = ?', $encrypted_password);
    $q->setHydrationMode(Doctrine::HYDRATE_SINGLE_SCALAR);
    $found = $q->fetchOne();
    if ($found != $id) {
      $form->getElement('old_password')->addError("Old password is incorrect.");
      throw new Validate_Exception();
    }
    // Password matches, do change
    $user = $this->find($id);
    $user->setPassword($data['password']);
    $user->save();
  }

  /**
   * Password reset for admin with this email
   * Returns array with id, username, firstname, lastname, email, token, and pin
   * @param string $email
   * @return array
   */
  public function resetPassword($email) {
    $q = Doctrine_Query::create();
    $q->select('id, username, firstname, lastname, email');
    $q->from('Admin');
    $q->where("active = true");
    $q->andWhere('email LIKE ?', $email);
    $q->setHydrationMode(Doctrine::HYDRATE_RECORD);
    $user = $q->fetchOne();
    if (!$user) {
      throw new Validate_Exception("No account with matching email address.");
    }
    // Generate token
    $token = md5(uniqid(time() . $user->email, true));
    $pin = rand(1000, 9999);
    // Save token
    $user->setToken($token);
    $user->setPasswordKey($pin);
    $user->token_date = date('Y-m-d');
    $user->save();
    return array(
      'id' => $user->id,
      'username' => $user->username,
      'firstname' => $user->firstname,
      'lastname' => $user->lastname,
      'email' => $user->email,
      'token' => $token,
      'pin' => $pin
    );
  }

  /**
   * Confirms the password reset with token and PIN
   * Changes the password
   * Logs the user in
   * @param type $token
   * @param Form_ChangePassword $form
   * @return array
   */
  public function confirmPasswordReset($token, Form_ChangePassword $form) {
    $values = $form->getValues();
    // Make sure token and pin are correct
    $token = Admin::encryptToken($token);
    $pin = Admin::encryptPin($values['pin']);
    $date = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') - 2, date('Y')));
    $q = Doctrine_Query::create();
    $q->select('id, username, token_date');
    $q->from('Admin');
    $q->where("active = true");
    $q->andWhere('token LIKE ?', $token);
    $q->andWhere('password_key = ?', $pin);
    //$q->andWhere('token_date >= ?', $date);
    $q->setHydrationMode(Doctrine::HYDRATE_ARRAY);
    $user = $q->fetchOne();
    if (!$user) {
      $form->getElement('pin')->addError("Could not find match for given PIN number.");
      throw new Validate_Exception();
    }
    if ($user['token_date'] < $date) {
      $form->getElement('pin')->addError("This PIN number has expired.");
      throw new Validate_Exception();
    }
    // Password matches, do change
    $mgr = $this->find($user['id']);
    $mgr->setPassword($values['password']);
    $mgr->save();
    return $this->login($user['username'], $values['password']);
  }

}