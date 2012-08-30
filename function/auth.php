<?php
    if (preg_match ("/auth.php/", $_SERVER['PHP_SELF']))
    {
        header ("Location: /index.php");
        exit;
    }

    /*
    #
    # Copyright Iulian Ciobanu (CIGraphics) 2009
    # Email: cigraphics@gmail.com
    # Please leave the copyright and email intact.
    #
    */
    class Auth {

        var $type = 'cookie';
        private $connection;
        private $errors = array();
        var $minval = 2;
        var $maxval = 22;
        var $minpass = 0;
        var $salt = '#@()DIJK#)(F#&*()DS#@JKS)@(I()#@DU)*(&@#)(#U)J';
        var $emailAuth = false;

        function __construct() {
            if ( $this->type == 'session' ) {
                session_start();
            }
            $this->check();
        }

        public function login($user, $pass) {

            global $db;

            $email = $this->emailAuth;
            $err = false;
            $user = ($user);
            $password = $this->encrypt($pass);
            if ( $email == true ) {
                if ( !$this->email($user) ) {
                    $this->errors[] = 'Email invalid.';
                    $err = true;
                } else {
                    $col = 'email';
                }
            } else {
                if ( !$this->name($user) ) {
                    $this->errors[] = 'Name invalid. Min chars: '.$this->minval.'. Max chars: '.$this->maxval;
                    $err = true;
                } else {
                    $col = 'user';
                }
            }
            if ( strlen($pass) < $this->minpass ) {
                $this->errors[] = 'Password min value is 6 chars.';
                $err = true;
            }

            if ( $err == false ) {

                $sql = sprintf("SELECT * FROM users WHERE %s = '%s'", $col, $user);
                $q = $db->prepare($sql);
                if ($q->execute() == TRUE) {
                    $result = $q->fetch();
                } else {
                    print_r($q->errorInfo());
                    die();
                }
                if ( count($result) == 0 ) {
                    $this->errors[] = ucfirst($col).' doesn\'t exist.';
                } else {
                    $row = &$result; 
                    if ( $row['password'] == $password ) {
                        if ( $this->type == 'session' ) {
                            $this->set_session($col, $user);
                            $this->set_session('password', $password);
                            $this->set_session('group', $row['group']);
                        } elseif ( $this->type == 'cookie' ) {
                            $this->set_cookie($col, $user);
                            $this->set_cookie('password', $password);
                            $this->set_cookie('group', $row['group']);
                        }
                        header('Location: '.$_SERVER['PHP_SELF']);
                    } else {
                        $this->errors[] = 'Incorrect password';
                    }
                }

            }
        }

        public function encrypt($value) {
            $enc = md5($this->salt.md5($value));
            return sha1($enc);
        }

        // Email validation
        private function email($email) {
            $reg = "#^(((([a-z\d][\.\-\+_]?)*)[a-z0-9])+)\@(((([a-z\d][\.\-_]?){0,62})[a-z\d])+)\.([a-z\d]{2,6})$#i";
            if ( !preg_match($reg, $email) ) {
                return false;
            } else {
                return true;
            }
        }

        // Name validation
        private function name($name) {
            $min = $this->minval - 2;
            if ( !preg_match("#^[a-z][\da-z_]{".$min.",".$this->maxval."}[a-z\d]\$#i", $name) ) {
                return false;
            } else {
                return true;
            }
        }

        private function set_session($name, $value) {
            $_SESSION[$name] = $value;
        }

        private function destroy_session() {
            session_unset();
            session_destroy();
        }

        private function set_cookie($name, $value, $time = 3600 ) {
            setcookie($name, $value, time()+$time, '/');
        }

        private function destroy_cookie($name) {
            setcookie($name, '', time()-1, '/');
        }

        public function logout() {
            if ( $this->emailAuth == false ) {
                $col = 'user';
            } else {
                $col = 'email';
            }
            if ( $this->type == 'session' ) {
                $this->destroy_session();
            } elseif ( $this->type == 'cookie' ) {
                $this->destroy_cookie('password');
                $this->destroy_cookie('group');
                $this->destroy_cookie($col);
            }
            header('Location: '.$_SERVER['PHP_SELF']);
        }

        private function check() {

            global $db;

            if ( $this->emailAuth == false ) {
                $col = 'user';
            } else {
                $col = 'email';
            }
            if ( $this->type == 'cookie' ) {
                if ( isset($_COOKIE['password']) ) {
                    $sql = sprintf("SELECT * FROM users WHERE %s = '%s'", $col, $_COOKIE[$col] );
                    $q = $db->prepare($sql);
                    if ($q->execute() == TRUE) {
                        $result = $q->fetch();
                    } else {
                        print_r($q->errorInfo());
                        die();
                    }
                    $row = &$result;
                    if ( $row[$col] !== $_COOKIE[$col] || $row['password'] !== $_COOKIE['password'] ) {
                        $this->logout();
                    }
                }
            } elseif ( $this->type == 'session' ) {
                if ( isset($_SESSION['password']) ) {
                    $sql = sprintf("SELECT * FROM users WHERE %s = '%s'", $col, $_COOKIE[$col] );
                    $q = $db->prepare($sql);
                    if ($q->execute() == TRUE) {
                        $result = $q->fetch();
                    } else {
                        print_r($q->errorInfo());
                        die();
                    }
                    $row = &$result;
                    if ( $row[$col] !== $_SESSION[$col] || $row['password'] !== $_SESSION['password'] ) {
                        $this->logout();
                    }
                }
            }
        }

        public function error() {
            $data = '';
            if ( is_array($this->errors) && !empty($this->errors) ) {
                $data = '<div align="center" style="border:1px solid #CCC; background-color:#FAFAFA; color:#FF0000">';
                foreach ( $this->errors as $value ) {
                    $data .= $value."<br />";
                }
                $data .= '</div>';
            }
            return $data;
        }

        public function isLoggedIn() {
            $ret = false;
            if ( $this->emailAuth == false ) {
                $col = 'user';
            } else {
                $col = 'email';
            }
            if ( $this->type == 'cookie' ) {
                if ( isset($_COOKIE['password']) ) {
                    $ret = true;
                }
            } elseif ( $this->type == 'session' ) {
                if ( isset($_SESSION['password']) ) {
                    $ret = true;
                }
            }
            return $ret;
        }
        public function isLoggedInAdmin($true = 0) {
            $ret = false;
            if ( $this->emailAuth == false ) {
                $col = 'user';
            } else {
                $col = 'email';
            }
            if ( $this->type == 'cookie' ) {
                if ( isset($_COOKIE['password']) && $_COOKIE['group'] == 'admin') {
                    $ret = true;
                }elseif(isset($_COOKIE['password']) && $_COOKIE['group'] != 'admin'){
                    $this->errors[] = 'Pls login with Admin account';
                }
            } elseif ( $this->type == 'session' ) {
                if ( isset($_SESSION['password']) && $_SESSION['group'] == 'admin') {
                    $ret = true;
                }elseif(isset($_COOKIE['password']) && $_COOKIE['group'] != 'admin'){
                    $this->errors[] = 'Pls login with Admin account';
                }
            }
            return $ret;
        }

    }
?>