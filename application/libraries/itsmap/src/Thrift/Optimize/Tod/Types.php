<?php
namespace Optimize\Tod;

/**
 * Autogenerated by Thrift Compiler (0.10.0)
 *
 * DO NOT EDIT UNLESS YOU ARE SURE THAT YOU KNOW WHAT YOU ARE DOING
 *  @generated
 */
use Thrift\Base\TBase;
use Thrift\Type\TType;
use Thrift\Type\TMessageType;
use Thrift\Exception\TException;
use Thrift\Exception\TProtocolException;
use Thrift\Protocol\TProtocol;
use Thrift\Protocol\TBinaryProtocolAccelerated;
use Thrift\Exception\TApplicationException;


class JunctionMovements {
  static $_TSPEC;

  /**
   * @var string
   */
  public $junction_id = null;
  /**
   * @var string
   */
  public $movements = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'junction_id',
          'type' => TType::STRING,
          ),
        2 => array(
          'var' => 'movements',
          'type' => TType::STRING,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['junction_id'])) {
        $this->junction_id = $vals['junction_id'];
      }
      if (isset($vals['movements'])) {
        $this->movements = $vals['movements'];
      }
    }
  }

  public function getName() {
    return 'JunctionMovements';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 1:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->junction_id);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->movements);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('JunctionMovements');
    if ($this->junction_id !== null) {
      $xfer += $output->writeFieldBegin('junction_id', TType::STRING, 1);
      $xfer += $output->writeString($this->junction_id);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->movements !== null) {
      $xfer += $output->writeFieldBegin('movements', TType::STRING, 2);
      $xfer += $output->writeString($this->movements);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class Tod {
  static $_TSPEC;

  /**
   * @var string
   */
  public $tod_name = null;
  /**
   * @var string
   */
  public $tod_start_time = null;
  /**
   * @var string
   */
  public $tod_end_time = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'tod_name',
          'type' => TType::STRING,
          ),
        2 => array(
          'var' => 'tod_start_time',
          'type' => TType::STRING,
          ),
        3 => array(
          'var' => 'tod_end_time',
          'type' => TType::STRING,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['tod_name'])) {
        $this->tod_name = $vals['tod_name'];
      }
      if (isset($vals['tod_start_time'])) {
        $this->tod_start_time = $vals['tod_start_time'];
      }
      if (isset($vals['tod_end_time'])) {
        $this->tod_end_time = $vals['tod_end_time'];
      }
    }
  }

  public function getName() {
    return 'Tod';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 1:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->tod_name);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->tod_start_time);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 3:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->tod_end_time);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('Tod');
    if ($this->tod_name !== null) {
      $xfer += $output->writeFieldBegin('tod_name', TType::STRING, 1);
      $xfer += $output->writeString($this->tod_name);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->tod_start_time !== null) {
      $xfer += $output->writeFieldBegin('tod_start_time', TType::STRING, 2);
      $xfer += $output->writeString($this->tod_start_time);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->tod_end_time !== null) {
      $xfer += $output->writeFieldBegin('tod_end_time', TType::STRING, 3);
      $xfer += $output->writeString($this->tod_end_time);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class Data {
  static $_TSPEC;

  /**
   * @var \Optimize\Tod\Tod[]
   */
  public $tod_plans = null;
  /**
   * @var string[]
   */
  public $cut_time = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'tod_plans',
          'type' => TType::LST,
          'etype' => TType::STRUCT,
          'elem' => array(
            'type' => TType::STRUCT,
            'class' => '\Optimize\Tod\Tod',
            ),
          ),
        2 => array(
          'var' => 'cut_time',
          'type' => TType::LST,
          'etype' => TType::STRING,
          'elem' => array(
            'type' => TType::STRING,
            ),
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['tod_plans'])) {
        $this->tod_plans = $vals['tod_plans'];
      }
      if (isset($vals['cut_time'])) {
        $this->cut_time = $vals['cut_time'];
      }
    }
  }

  public function getName() {
    return 'Data';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 1:
          if ($ftype == TType::LST) {
            $this->tod_plans = array();
            $_size0 = 0;
            $_etype3 = 0;
            $xfer += $input->readListBegin($_etype3, $_size0);
            for ($_i4 = 0; $_i4 < $_size0; ++$_i4)
            {
              $elem5 = null;
              $elem5 = new \Optimize\Tod\Tod();
              $xfer += $elem5->read($input);
              $this->tod_plans []= $elem5;
            }
            $xfer += $input->readListEnd();
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::LST) {
            $this->cut_time = array();
            $_size6 = 0;
            $_etype9 = 0;
            $xfer += $input->readListBegin($_etype9, $_size6);
            for ($_i10 = 0; $_i10 < $_size6; ++$_i10)
            {
              $elem11 = null;
              $xfer += $input->readString($elem11);
              $this->cut_time []= $elem11;
            }
            $xfer += $input->readListEnd();
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('Data');
    if ($this->tod_plans !== null) {
      if (!is_array($this->tod_plans)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('tod_plans', TType::LST, 1);
      {
        $output->writeListBegin(TType::STRUCT, count($this->tod_plans));
        {
          foreach ($this->tod_plans as $iter12)
          {
            $xfer += $iter12->write($output);
          }
        }
        $output->writeListEnd();
      }
      $xfer += $output->writeFieldEnd();
    }
    if ($this->cut_time !== null) {
      if (!is_array($this->cut_time)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('cut_time', TType::LST, 2);
      {
        $output->writeListBegin(TType::STRING, count($this->cut_time));
        {
          foreach ($this->cut_time as $iter13)
          {
            $xfer += $output->writeString($iter13);
          }
        }
        $output->writeListEnd();
      }
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class TodResponse {
  static $_TSPEC;

  /**
   * @var string
   */
  public $errno = null;
  /**
   * @var string
   */
  public $errmsg = null;
  /**
   * @var \Optimize\Tod\Data
   */
  public $response_data = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'errno',
          'type' => TType::STRING,
          ),
        2 => array(
          'var' => 'errmsg',
          'type' => TType::STRING,
          ),
        3 => array(
          'var' => 'response_data',
          'type' => TType::STRUCT,
          'class' => '\Optimize\Tod\Data',
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['errno'])) {
        $this->errno = $vals['errno'];
      }
      if (isset($vals['errmsg'])) {
        $this->errmsg = $vals['errmsg'];
      }
      if (isset($vals['response_data'])) {
        $this->response_data = $vals['response_data'];
      }
    }
  }

  public function getName() {
    return 'TodResponse';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 1:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->errno);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->errmsg);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 3:
          if ($ftype == TType::STRUCT) {
            $this->response_data = new \Optimize\Tod\Data();
            $xfer += $this->response_data->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('TodResponse');
    if ($this->errno !== null) {
      $xfer += $output->writeFieldBegin('errno', TType::STRING, 1);
      $xfer += $output->writeString($this->errno);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->errmsg !== null) {
      $xfer += $output->writeFieldBegin('errmsg', TType::STRING, 2);
      $xfer += $output->writeString($this->errmsg);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->response_data !== null) {
      if (!is_object($this->response_data)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('response_data', TType::STRUCT, 3);
      $xfer += $this->response_data->write($output);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}


