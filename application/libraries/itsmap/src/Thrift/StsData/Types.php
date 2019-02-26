<?php
namespace StsData;

/**
 * Autogenerated by Thrift Compiler (0.9.2)
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


class RoadVersionRuntime {
  static $_TSPEC;

  /**
   * @var string
   */
  public $dateDay = null;
  /**
   * @var string
   */
  public $startTime = null;
  /**
   * @var string
   */
  public $endTime = null;
  /**
   * @var string
   */
  public $roadVersion = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'dateDay',
          'type' => TType::STRING,
          ),
        2 => array(
          'var' => 'startTime',
          'type' => TType::STRING,
          ),
        3 => array(
          'var' => 'endTime',
          'type' => TType::STRING,
          ),
        4 => array(
          'var' => 'roadVersion',
          'type' => TType::STRING,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['dateDay'])) {
        $this->dateDay = $vals['dateDay'];
      }
      if (isset($vals['startTime'])) {
        $this->startTime = $vals['startTime'];
      }
      if (isset($vals['endTime'])) {
        $this->endTime = $vals['endTime'];
      }
      if (isset($vals['roadVersion'])) {
        $this->roadVersion = $vals['roadVersion'];
      }
    }
  }

  public function getName() {
    return 'RoadVersionRuntime';
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
            $xfer += $input->readString($this->dateDay);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->startTime);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 3:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->endTime);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 4:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->roadVersion);
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
    $xfer += $output->writeStructBegin('RoadVersionRuntime');
    if ($this->dateDay !== null) {
      $xfer += $output->writeFieldBegin('dateDay', TType::STRING, 1);
      $xfer += $output->writeString($this->dateDay);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->startTime !== null) {
      $xfer += $output->writeFieldBegin('startTime', TType::STRING, 2);
      $xfer += $output->writeString($this->startTime);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->endTime !== null) {
      $xfer += $output->writeFieldBegin('endTime', TType::STRING, 3);
      $xfer += $output->writeString($this->endTime);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->roadVersion !== null) {
      $xfer += $output->writeFieldBegin('roadVersion', TType::STRING, 4);
      $xfer += $output->writeString($this->roadVersion);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}


