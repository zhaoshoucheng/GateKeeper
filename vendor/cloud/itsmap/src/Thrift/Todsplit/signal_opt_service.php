<?php
namespace Todsplit;
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


interface signal_opt_serviceIf {
  /**
   * @param \Todsplit\Version[] $version
   * @param \Todsplit\SignalPlan $origin_signal_plan
   * @return \Todsplit\GreenSplitOptResponse
   */
  public function green_split_opt(array $version, \Todsplit\SignalPlan $origin_signal_plan);
  /**
   * @param \Todsplit\TodInfo $tod_info
   * @return \Todsplit\TodPlans
   */
  public function tod_opt(\Todsplit\TodInfo $tod_info);
}


class signal_opt_serviceClient implements \Todsplit\signal_opt_serviceIf {
  protected $input_ = null;
  protected $output_ = null;

  protected $seqid_ = 0;

  public function __construct($input, $output=null) {
    $this->input_ = $input;
    $this->output_ = $output ? $output : $input;
  }

  public function green_split_opt(array $version, \Todsplit\SignalPlan $origin_signal_plan)
  {
    $this->send_green_split_opt($version, $origin_signal_plan);
    return $this->recv_green_split_opt();
  }

  public function send_green_split_opt(array $version, \Todsplit\SignalPlan $origin_signal_plan)
  {
    $args = new \Todsplit\signal_opt_service_green_split_opt_args();
    $args->version = $version;
    $args->origin_signal_plan = $origin_signal_plan;
    $bin_accel = ($this->output_ instanceof TBinaryProtocolAccelerated) && function_exists('thrift_protocol_write_binary');
    if ($bin_accel)
    {
      thrift_protocol_write_binary($this->output_, 'green_split_opt', TMessageType::CALL, $args, $this->seqid_, $this->output_->isStrictWrite());
    }
    else
    {
      $this->output_->writeMessageBegin('green_split_opt', TMessageType::CALL, $this->seqid_);
      $args->write($this->output_);
      $this->output_->writeMessageEnd();
      $this->output_->getTransport()->flush();
    }
  }

  public function recv_green_split_opt()
  {
    $bin_accel = ($this->input_ instanceof TBinaryProtocolAccelerated) && function_exists('thrift_protocol_read_binary');
    if ($bin_accel) $result = thrift_protocol_read_binary($this->input_, '\Todsplit\signal_opt_service_green_split_opt_result', $this->input_->isStrictRead());
    else
    {
      $rseqid = 0;
      $fname = null;
      $mtype = 0;

      $this->input_->readMessageBegin($fname, $mtype, $rseqid);
      if ($mtype == TMessageType::EXCEPTION) {
        $x = new TApplicationException();
        $x->read($this->input_);
        $this->input_->readMessageEnd();
        throw $x;
      }
      $result = new \Todsplit\signal_opt_service_green_split_opt_result();
      $result->read($this->input_);
      $this->input_->readMessageEnd();
    }
    if ($result->success !== null) {
      return $result->success;
    }
    throw new \Exception("green_split_opt failed: unknown result");
  }

  public function tod_opt(\Todsplit\TodInfo $tod_info)
  {
    $this->send_tod_opt($tod_info);
    return $this->recv_tod_opt();
  }

  public function send_tod_opt(\Todsplit\TodInfo $tod_info)
  {
    $args = new \Todsplit\signal_opt_service_tod_opt_args();
    $args->tod_info = $tod_info;
    $bin_accel = ($this->output_ instanceof TBinaryProtocolAccelerated) && function_exists('thrift_protocol_write_binary');
    if ($bin_accel)
    {
      thrift_protocol_write_binary($this->output_, 'tod_opt', TMessageType::CALL, $args, $this->seqid_, $this->output_->isStrictWrite());
    }
    else
    {
      $this->output_->writeMessageBegin('tod_opt', TMessageType::CALL, $this->seqid_);
      $args->write($this->output_);
      $this->output_->writeMessageEnd();
      $this->output_->getTransport()->flush();
    }
  }

  public function recv_tod_opt()
  {
    $bin_accel = ($this->input_ instanceof TBinaryProtocolAccelerated) && function_exists('thrift_protocol_read_binary');
    if ($bin_accel) $result = thrift_protocol_read_binary($this->input_, '\Todsplit\signal_opt_service_tod_opt_result', $this->input_->isStrictRead());
    else
    {
      $rseqid = 0;
      $fname = null;
      $mtype = 0;

      $this->input_->readMessageBegin($fname, $mtype, $rseqid);
      if ($mtype == TMessageType::EXCEPTION) {
        $x = new TApplicationException();
        $x->read($this->input_);
        $this->input_->readMessageEnd();
        throw $x;
      }
      $result = new \Todsplit\signal_opt_service_tod_opt_result();
      $result->read($this->input_);
      $this->input_->readMessageEnd();
    }
    if ($result->success !== null) {
      return $result->success;
    }
    throw new \Exception("tod_opt failed: unknown result");
  }

}


// HELPER FUNCTIONS AND STRUCTURES

class signal_opt_service_green_split_opt_args {
  static $_TSPEC;

  /**
   * @var \Todsplit\Version[]
   */
  public $version = null;
  /**
   * @var \Todsplit\SignalPlan
   */
  public $origin_signal_plan = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'version',
          'type' => TType::LST,
          'etype' => TType::STRUCT,
          'elem' => array(
            'type' => TType::STRUCT,
            'class' => '\Todsplit\Version',
            ),
          ),
        2 => array(
          'var' => 'origin_signal_plan',
          'type' => TType::STRUCT,
          'class' => '\Todsplit\SignalPlan',
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['version'])) {
        $this->version = $vals['version'];
      }
      if (isset($vals['origin_signal_plan'])) {
        $this->origin_signal_plan = $vals['origin_signal_plan'];
      }
    }
  }

  public function getName() {
    return 'signal_opt_service_green_split_opt_args';
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
            $this->version = array();
            $_size95 = 0;
            $_etype98 = 0;
            $xfer += $input->readListBegin($_etype98, $_size95);
            for ($_i99 = 0; $_i99 < $_size95; ++$_i99)
            {
              $elem100 = null;
              $elem100 = new \Todsplit\Version();
              $xfer += $elem100->read($input);
              $this->version []= $elem100;
            }
            $xfer += $input->readListEnd();
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::STRUCT) {
            $this->origin_signal_plan = new \Todsplit\SignalPlan();
            $xfer += $this->origin_signal_plan->read($input);
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
    $xfer += $output->writeStructBegin('signal_opt_service_green_split_opt_args');
    if ($this->version !== null) {
      if (!is_array($this->version)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('version', TType::LST, 1);
      {
        $output->writeListBegin(TType::STRUCT, count($this->version));
        {
          foreach ($this->version as $iter101)
          {
            $xfer += $iter101->write($output);
          }
        }
        $output->writeListEnd();
      }
      $xfer += $output->writeFieldEnd();
    }
    if ($this->origin_signal_plan !== null) {
      if (!is_object($this->origin_signal_plan)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('origin_signal_plan', TType::STRUCT, 2);
      $xfer += $this->origin_signal_plan->write($output);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class signal_opt_service_green_split_opt_result {
  static $_TSPEC;

  /**
   * @var \Todsplit\GreenSplitOptResponse
   */
  public $success = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        0 => array(
          'var' => 'success',
          'type' => TType::STRUCT,
          'class' => '\Todsplit\GreenSplitOptResponse',
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['success'])) {
        $this->success = $vals['success'];
      }
    }
  }

  public function getName() {
    return 'signal_opt_service_green_split_opt_result';
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
        case 0:
          if ($ftype == TType::STRUCT) {
            $this->success = new \Todsplit\GreenSplitOptResponse();
            $xfer += $this->success->read($input);
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
    $xfer += $output->writeStructBegin('signal_opt_service_green_split_opt_result');
    if ($this->success !== null) {
      if (!is_object($this->success)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('success', TType::STRUCT, 0);
      $xfer += $this->success->write($output);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class signal_opt_service_tod_opt_args {
  static $_TSPEC;

  /**
   * @var \Todsplit\TodInfo
   */
  public $tod_info = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'tod_info',
          'type' => TType::STRUCT,
          'class' => '\Todsplit\TodInfo',
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['tod_info'])) {
        $this->tod_info = $vals['tod_info'];
      }
    }
  }

  public function getName() {
    return 'signal_opt_service_tod_opt_args';
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
          if ($ftype == TType::STRUCT) {
            $this->tod_info = new \Todsplit\TodInfo();
            $xfer += $this->tod_info->read($input);
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
    $xfer += $output->writeStructBegin('signal_opt_service_tod_opt_args');
    if ($this->tod_info !== null) {
      if (!is_object($this->tod_info)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('tod_info', TType::STRUCT, 1);
      $xfer += $this->tod_info->write($output);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class signal_opt_service_tod_opt_result {
  static $_TSPEC;

  /**
   * @var \Todsplit\TodPlans
   */
  public $success = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        0 => array(
          'var' => 'success',
          'type' => TType::STRUCT,
          'class' => '\Todsplit\TodPlans',
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['success'])) {
        $this->success = $vals['success'];
      }
    }
  }

  public function getName() {
    return 'signal_opt_service_tod_opt_result';
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
        case 0:
          if ($ftype == TType::STRUCT) {
            $this->success = new \Todsplit\TodPlans();
            $xfer += $this->success->read($input);
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
    $xfer += $output->writeStructBegin('signal_opt_service_tod_opt_result');
    if ($this->success !== null) {
      if (!is_object($this->success)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('success', TType::STRUCT, 0);
      $xfer += $this->success->write($output);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}


