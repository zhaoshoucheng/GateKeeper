<?php
namespace Optimize\Greensplit;

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


class SignalOfGreen {
  static $_TSPEC;

  /**
   * @var int
   */
  public $green_start = null;
  /**
   * @var int
   */
  public $green_duration = null;
  /**
   * @var int
   */
  public $yellow = null;
  /**
   * @var int
   */
  public $red_clean = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'green_start',
          'type' => TType::I32,
          ),
        2 => array(
          'var' => 'green_duration',
          'type' => TType::I32,
          ),
        3 => array(
          'var' => 'yellow',
          'type' => TType::I32,
          ),
        4 => array(
          'var' => 'red_clean',
          'type' => TType::I32,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['green_start'])) {
        $this->green_start = $vals['green_start'];
      }
      if (isset($vals['green_duration'])) {
        $this->green_duration = $vals['green_duration'];
      }
      if (isset($vals['yellow'])) {
        $this->yellow = $vals['yellow'];
      }
      if (isset($vals['red_clean'])) {
        $this->red_clean = $vals['red_clean'];
      }
    }
  }

  public function getName() {
    return 'SignalOfGreen';
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
          if ($ftype == TType::I32) {
            $xfer += $input->readI32($this->green_start);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::I32) {
            $xfer += $input->readI32($this->green_duration);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 3:
          if ($ftype == TType::I32) {
            $xfer += $input->readI32($this->yellow);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 4:
          if ($ftype == TType::I32) {
            $xfer += $input->readI32($this->red_clean);
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
    $xfer += $output->writeStructBegin('SignalOfGreen');
    if ($this->green_start !== null) {
      $xfer += $output->writeFieldBegin('green_start', TType::I32, 1);
      $xfer += $output->writeI32($this->green_start);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->green_duration !== null) {
      $xfer += $output->writeFieldBegin('green_duration', TType::I32, 2);
      $xfer += $output->writeI32($this->green_duration);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->yellow !== null) {
      $xfer += $output->writeFieldBegin('yellow', TType::I32, 3);
      $xfer += $output->writeI32($this->yellow);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->red_clean !== null) {
      $xfer += $output->writeFieldBegin('red_clean', TType::I32, 4);
      $xfer += $output->writeI32($this->red_clean);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class MovementSignal {
  static $_TSPEC;

  /**
   * @var string
   */
  public $logic_flow_id = null;
  /**
   * @var \Optimize\Greensplit\SignalOfGreen[]
   */
  public $signal_of_green = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'logic_flow_id',
          'type' => TType::STRING,
          ),
        2 => array(
          'var' => 'signal_of_green',
          'type' => TType::LST,
          'etype' => TType::STRUCT,
          'elem' => array(
            'type' => TType::STRUCT,
            'class' => '\Optimize\Greensplit\SignalOfGreen',
            ),
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['logic_flow_id'])) {
        $this->logic_flow_id = $vals['logic_flow_id'];
      }
      if (isset($vals['signal_of_green'])) {
        $this->signal_of_green = $vals['signal_of_green'];
      }
    }
  }

  public function getName() {
    return 'MovementSignal';
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
            $xfer += $input->readString($this->logic_flow_id);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::LST) {
            $this->signal_of_green = array();
            $_size0 = 0;
            $_etype3 = 0;
            $xfer += $input->readListBegin($_etype3, $_size0);
            for ($_i4 = 0; $_i4 < $_size0; ++$_i4)
            {
              $elem5 = null;
              $elem5 = new \Optimize\Greensplit\SignalOfGreen();
              $xfer += $elem5->read($input);
              $this->signal_of_green []= $elem5;
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
    $xfer += $output->writeStructBegin('MovementSignal');
    if ($this->logic_flow_id !== null) {
      $xfer += $output->writeFieldBegin('logic_flow_id', TType::STRING, 1);
      $xfer += $output->writeString($this->logic_flow_id);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->signal_of_green !== null) {
      if (!is_array($this->signal_of_green)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('signal_of_green', TType::LST, 2);
      {
        $output->writeListBegin(TType::STRUCT, count($this->signal_of_green));
        {
          foreach ($this->signal_of_green as $iter6)
          {
            $xfer += $iter6->write($output);
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

class SignalPlan {
  static $_TSPEC;

  /**
   * @var string
   */
  public $logic_junction_id = null;
  /**
   * @var string
   */
  public $dates = null;
  /**
   * @var string
   */
  public $start_time = null;
  /**
   * @var string
   */
  public $end_time = null;
  /**
   * @var int
   */
  public $cycle = null;
  /**
   * @var int
   */
  public $offset = null;
  /**
   * @var int
   */
  public $clock_shift = null;
  /**
   * @var \Optimize\Greensplit\MovementSignal[]
   */
  public $signal = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'logic_junction_id',
          'type' => TType::STRING,
          ),
        2 => array(
          'var' => 'dates',
          'type' => TType::STRING,
          ),
        3 => array(
          'var' => 'start_time',
          'type' => TType::STRING,
          ),
        4 => array(
          'var' => 'end_time',
          'type' => TType::STRING,
          ),
        5 => array(
          'var' => 'cycle',
          'type' => TType::I32,
          ),
        6 => array(
          'var' => 'offset',
          'type' => TType::I32,
          ),
        7 => array(
          'var' => 'clock_shift',
          'type' => TType::I32,
          ),
        8 => array(
          'var' => 'signal',
          'type' => TType::LST,
          'etype' => TType::STRUCT,
          'elem' => array(
            'type' => TType::STRUCT,
            'class' => '\Optimize\Greensplit\MovementSignal',
            ),
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['logic_junction_id'])) {
        $this->logic_junction_id = $vals['logic_junction_id'];
      }
      if (isset($vals['dates'])) {
        $this->dates = $vals['dates'];
      }
      if (isset($vals['start_time'])) {
        $this->start_time = $vals['start_time'];
      }
      if (isset($vals['end_time'])) {
        $this->end_time = $vals['end_time'];
      }
      if (isset($vals['cycle'])) {
        $this->cycle = $vals['cycle'];
      }
      if (isset($vals['offset'])) {
        $this->offset = $vals['offset'];
      }
      if (isset($vals['clock_shift'])) {
        $this->clock_shift = $vals['clock_shift'];
      }
      if (isset($vals['signal'])) {
        $this->signal = $vals['signal'];
      }
    }
  }

  public function getName() {
    return 'SignalPlan';
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
            $xfer += $input->readString($this->logic_junction_id);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->dates);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 3:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->start_time);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 4:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->end_time);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 5:
          if ($ftype == TType::I32) {
            $xfer += $input->readI32($this->cycle);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 6:
          if ($ftype == TType::I32) {
            $xfer += $input->readI32($this->offset);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 7:
          if ($ftype == TType::I32) {
            $xfer += $input->readI32($this->clock_shift);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 8:
          if ($ftype == TType::LST) {
            $this->signal = array();
            $_size7 = 0;
            $_etype10 = 0;
            $xfer += $input->readListBegin($_etype10, $_size7);
            for ($_i11 = 0; $_i11 < $_size7; ++$_i11)
            {
              $elem12 = null;
              $elem12 = new \Optimize\Greensplit\MovementSignal();
              $xfer += $elem12->read($input);
              $this->signal []= $elem12;
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
    $xfer += $output->writeStructBegin('SignalPlan');
    if ($this->logic_junction_id !== null) {
      $xfer += $output->writeFieldBegin('logic_junction_id', TType::STRING, 1);
      $xfer += $output->writeString($this->logic_junction_id);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->dates !== null) {
      $xfer += $output->writeFieldBegin('dates', TType::STRING, 2);
      $xfer += $output->writeString($this->dates);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->start_time !== null) {
      $xfer += $output->writeFieldBegin('start_time', TType::STRING, 3);
      $xfer += $output->writeString($this->start_time);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->end_time !== null) {
      $xfer += $output->writeFieldBegin('end_time', TType::STRING, 4);
      $xfer += $output->writeString($this->end_time);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->cycle !== null) {
      $xfer += $output->writeFieldBegin('cycle', TType::I32, 5);
      $xfer += $output->writeI32($this->cycle);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->offset !== null) {
      $xfer += $output->writeFieldBegin('offset', TType::I32, 6);
      $xfer += $output->writeI32($this->offset);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->clock_shift !== null) {
      $xfer += $output->writeFieldBegin('clock_shift', TType::I32, 7);
      $xfer += $output->writeI32($this->clock_shift);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->signal !== null) {
      if (!is_array($this->signal)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('signal', TType::LST, 8);
      {
        $output->writeListBegin(TType::STRUCT, count($this->signal));
        {
          foreach ($this->signal as $iter13)
          {
            $xfer += $iter13->write($output);
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

class AdviceMes {
  static $_TSPEC;

  /**
   * @var string[]
   */
  public $over_saturation_flow = null;
  /**
   * @var string[]
   */
  public $green_loss_flow = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'over_saturation_flow',
          'type' => TType::LST,
          'etype' => TType::STRING,
          'elem' => array(
            'type' => TType::STRING,
            ),
          ),
        2 => array(
          'var' => 'green_loss_flow',
          'type' => TType::LST,
          'etype' => TType::STRING,
          'elem' => array(
            'type' => TType::STRING,
            ),
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['over_saturation_flow'])) {
        $this->over_saturation_flow = $vals['over_saturation_flow'];
      }
      if (isset($vals['green_loss_flow'])) {
        $this->green_loss_flow = $vals['green_loss_flow'];
      }
    }
  }

  public function getName() {
    return 'AdviceMes';
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
            $this->over_saturation_flow = array();
            $_size14 = 0;
            $_etype17 = 0;
            $xfer += $input->readListBegin($_etype17, $_size14);
            for ($_i18 = 0; $_i18 < $_size14; ++$_i18)
            {
              $elem19 = null;
              $xfer += $input->readString($elem19);
              $this->over_saturation_flow []= $elem19;
            }
            $xfer += $input->readListEnd();
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::LST) {
            $this->green_loss_flow = array();
            $_size20 = 0;
            $_etype23 = 0;
            $xfer += $input->readListBegin($_etype23, $_size20);
            for ($_i24 = 0; $_i24 < $_size20; ++$_i24)
            {
              $elem25 = null;
              $xfer += $input->readString($elem25);
              $this->green_loss_flow []= $elem25;
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
    $xfer += $output->writeStructBegin('AdviceMes');
    if ($this->over_saturation_flow !== null) {
      if (!is_array($this->over_saturation_flow)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('over_saturation_flow', TType::LST, 1);
      {
        $output->writeListBegin(TType::STRING, count($this->over_saturation_flow));
        {
          foreach ($this->over_saturation_flow as $iter26)
          {
            $xfer += $output->writeString($iter26);
          }
        }
        $output->writeListEnd();
      }
      $xfer += $output->writeFieldEnd();
    }
    if ($this->green_loss_flow !== null) {
      if (!is_array($this->green_loss_flow)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('green_loss_flow', TType::LST, 2);
      {
        $output->writeListBegin(TType::STRING, count($this->green_loss_flow));
        {
          foreach ($this->green_loss_flow as $iter27)
          {
            $xfer += $output->writeString($iter27);
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

class GreenSplitOptResponse {
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
   * @var \Optimize\Greensplit\SignalPlan
   */
  public $green_split_opt_signal_plan = null;
  /**
   * @var \Optimize\Greensplit\AdviceMes
   */
  public $advice_mes = null;

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
          'var' => 'green_split_opt_signal_plan',
          'type' => TType::STRUCT,
          'class' => '\Optimize\Greensplit\SignalPlan',
          ),
        4 => array(
          'var' => 'advice_mes',
          'type' => TType::STRUCT,
          'class' => '\Optimize\Greensplit\AdviceMes',
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
      if (isset($vals['green_split_opt_signal_plan'])) {
        $this->green_split_opt_signal_plan = $vals['green_split_opt_signal_plan'];
      }
      if (isset($vals['advice_mes'])) {
        $this->advice_mes = $vals['advice_mes'];
      }
    }
  }

  public function getName() {
    return 'GreenSplitOptResponse';
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
            $this->green_split_opt_signal_plan = new \Optimize\Greensplit\SignalPlan();
            $xfer += $this->green_split_opt_signal_plan->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 4:
          if ($ftype == TType::STRUCT) {
            $this->advice_mes = new \Optimize\Greensplit\AdviceMes();
            $xfer += $this->advice_mes->read($input);
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
    $xfer += $output->writeStructBegin('GreenSplitOptResponse');
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
    if ($this->green_split_opt_signal_plan !== null) {
      if (!is_object($this->green_split_opt_signal_plan)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('green_split_opt_signal_plan', TType::STRUCT, 3);
      $xfer += $this->green_split_opt_signal_plan->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->advice_mes !== null) {
      if (!is_object($this->advice_mes)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('advice_mes', TType::STRUCT, 4);
      $xfer += $this->advice_mes->write($output);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}


