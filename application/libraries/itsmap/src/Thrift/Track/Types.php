<?php
namespace Track;

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


class Rtime {
  static $_TSPEC;

  /**
   * @var string
   */
  public $mapVersion = null;
  /**
   * @var int
   */
  public $startTS = null;
  /**
   * @var int
   */
  public $endTS = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'mapVersion',
          'type' => TType::STRING,
          ),
        2 => array(
          'var' => 'startTS',
          'type' => TType::I64,
          ),
        3 => array(
          'var' => 'endTS',
          'type' => TType::I64,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['mapVersion'])) {
        $this->mapVersion = $vals['mapVersion'];
      }
      if (isset($vals['startTS'])) {
        $this->startTS = $vals['startTS'];
      }
      if (isset($vals['endTS'])) {
        $this->endTS = $vals['endTS'];
      }
    }
  }

  public function getName() {
    return 'Rtime';
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
            $xfer += $input->readString($this->mapVersion);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::I64) {
            $xfer += $input->readI64($this->startTS);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 3:
          if ($ftype == TType::I64) {
            $xfer += $input->readI64($this->endTS);
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
    $xfer += $output->writeStructBegin('Rtime');
    if ($this->mapVersion !== null) {
      $xfer += $output->writeFieldBegin('mapVersion', TType::STRING, 1);
      $xfer += $output->writeString($this->mapVersion);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->startTS !== null) {
      $xfer += $output->writeFieldBegin('startTS', TType::I64, 2);
      $xfer += $output->writeI64($this->startTS);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->endTS !== null) {
      $xfer += $output->writeFieldBegin('endTS', TType::I64, 3);
      $xfer += $output->writeI64($this->endTS);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class TypeData {
  static $_TSPEC;

  /**
   * @var bool
   */
  public $all = null;
  /**
   * @var int
   */
  public $noStop = null;
  /**
   * @var int
   */
  public $lR = null;
  /**
   * @var int
   */
  public $rR = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'all',
          'type' => TType::BOOL,
          ),
        2 => array(
          'var' => 'noStop',
          'type' => TType::I32,
          ),
        3 => array(
          'var' => 'lR',
          'type' => TType::I32,
          ),
        4 => array(
          'var' => 'rR',
          'type' => TType::I32,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['all'])) {
        $this->all = $vals['all'];
      }
      if (isset($vals['noStop'])) {
        $this->noStop = $vals['noStop'];
      }
      if (isset($vals['lR'])) {
        $this->lR = $vals['lR'];
      }
      if (isset($vals['rR'])) {
        $this->rR = $vals['rR'];
      }
    }
  }

  public function getName() {
    return 'TypeData';
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
          if ($ftype == TType::BOOL) {
            $xfer += $input->readBool($this->all);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::I32) {
            $xfer += $input->readI32($this->noStop);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 3:
          if ($ftype == TType::I32) {
            $xfer += $input->readI32($this->lR);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 4:
          if ($ftype == TType::I32) {
            $xfer += $input->readI32($this->rR);
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
    $xfer += $output->writeStructBegin('TypeData');
    if ($this->all !== null) {
      $xfer += $output->writeFieldBegin('all', TType::BOOL, 1);
      $xfer += $output->writeBool($this->all);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->noStop !== null) {
      $xfer += $output->writeFieldBegin('noStop', TType::I32, 2);
      $xfer += $output->writeI32($this->noStop);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->lR !== null) {
      $xfer += $output->writeFieldBegin('lR', TType::I32, 3);
      $xfer += $output->writeI32($this->lR);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->rR !== null) {
      $xfer += $output->writeFieldBegin('rR', TType::I32, 4);
      $xfer += $output->writeI32($this->rR);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class FilterData {
  static $_TSPEC;

  /**
   * @var int
   */
  public $xType = null;
  /**
   * @var \Track\TypeData
   */
  public $xData = null;
  /**
   * @var int
   */
  public $yType = null;
  /**
   * @var \Track\TypeData
   */
  public $yData = null;
  /**
   * @var int
   */
  public $num = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'xType',
          'type' => TType::I32,
          ),
        2 => array(
          'var' => 'xData',
          'type' => TType::STRUCT,
          'class' => '\Track\TypeData',
          ),
        3 => array(
          'var' => 'yType',
          'type' => TType::I32,
          ),
        4 => array(
          'var' => 'yData',
          'type' => TType::STRUCT,
          'class' => '\Track\TypeData',
          ),
        5 => array(
          'var' => 'num',
          'type' => TType::I32,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['xType'])) {
        $this->xType = $vals['xType'];
      }
      if (isset($vals['xData'])) {
        $this->xData = $vals['xData'];
      }
      if (isset($vals['yType'])) {
        $this->yType = $vals['yType'];
      }
      if (isset($vals['yData'])) {
        $this->yData = $vals['yData'];
      }
      if (isset($vals['num'])) {
        $this->num = $vals['num'];
      }
    }
  }

  public function getName() {
    return 'FilterData';
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
            $xfer += $input->readI32($this->xType);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::STRUCT) {
            $this->xData = new \Track\TypeData();
            $xfer += $this->xData->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 3:
          if ($ftype == TType::I32) {
            $xfer += $input->readI32($this->yType);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 4:
          if ($ftype == TType::STRUCT) {
            $this->yData = new \Track\TypeData();
            $xfer += $this->yData->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 5:
          if ($ftype == TType::I32) {
            $xfer += $input->readI32($this->num);
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
    $xfer += $output->writeStructBegin('FilterData');
    if ($this->xType !== null) {
      $xfer += $output->writeFieldBegin('xType', TType::I32, 1);
      $xfer += $output->writeI32($this->xType);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->xData !== null) {
      if (!is_object($this->xData)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('xData', TType::STRUCT, 2);
      $xfer += $this->xData->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->yType !== null) {
      $xfer += $output->writeFieldBegin('yType', TType::I32, 3);
      $xfer += $output->writeI32($this->yType);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->yData !== null) {
      if (!is_object($this->yData)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('yData', TType::STRUCT, 4);
      $xfer += $this->yData->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->num !== null) {
      $xfer += $output->writeFieldBegin('num', TType::I32, 5);
      $xfer += $output->writeI32($this->num);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class Request {
  static $_TSPEC;

  /**
   * @var string
   */
  public $junctionId = null;
  /**
   * @var string
   */
  public $flowId = null;
  /**
   * @var \Track\Rtime[]
   */
  public $rtimeVec = null;
  /**
   * @var \Track\FilterData[]
   */
  public $filterDataVec = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'junctionId',
          'type' => TType::STRING,
          ),
        2 => array(
          'var' => 'flowId',
          'type' => TType::STRING,
          ),
        3 => array(
          'var' => 'rtimeVec',
          'type' => TType::LST,
          'etype' => TType::STRUCT,
          'elem' => array(
            'type' => TType::STRUCT,
            'class' => '\Track\Rtime',
            ),
          ),
        4 => array(
          'var' => 'filterDataVec',
          'type' => TType::LST,
          'etype' => TType::STRUCT,
          'elem' => array(
            'type' => TType::STRUCT,
            'class' => '\Track\FilterData',
            ),
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['junctionId'])) {
        $this->junctionId = $vals['junctionId'];
      }
      if (isset($vals['flowId'])) {
        $this->flowId = $vals['flowId'];
      }
      if (isset($vals['rtimeVec'])) {
        $this->rtimeVec = $vals['rtimeVec'];
      }
      if (isset($vals['filterDataVec'])) {
        $this->filterDataVec = $vals['filterDataVec'];
      }
    }
  }

  public function getName() {
    return 'Request';
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
            $xfer += $input->readString($this->junctionId);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->flowId);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 3:
          if ($ftype == TType::LST) {
            $this->rtimeVec = array();
            $_size0 = 0;
            $_etype3 = 0;
            $xfer += $input->readListBegin($_etype3, $_size0);
            for ($_i4 = 0; $_i4 < $_size0; ++$_i4)
            {
              $elem5 = null;
              $elem5 = new \Track\Rtime();
              $xfer += $elem5->read($input);
              $this->rtimeVec []= $elem5;
            }
            $xfer += $input->readListEnd();
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 4:
          if ($ftype == TType::LST) {
            $this->filterDataVec = array();
            $_size6 = 0;
            $_etype9 = 0;
            $xfer += $input->readListBegin($_etype9, $_size6);
            for ($_i10 = 0; $_i10 < $_size6; ++$_i10)
            {
              $elem11 = null;
              $elem11 = new \Track\FilterData();
              $xfer += $elem11->read($input);
              $this->filterDataVec []= $elem11;
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
    $xfer += $output->writeStructBegin('Request');
    if ($this->junctionId !== null) {
      $xfer += $output->writeFieldBegin('junctionId', TType::STRING, 1);
      $xfer += $output->writeString($this->junctionId);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->flowId !== null) {
      $xfer += $output->writeFieldBegin('flowId', TType::STRING, 2);
      $xfer += $output->writeString($this->flowId);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->rtimeVec !== null) {
      if (!is_array($this->rtimeVec)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('rtimeVec', TType::LST, 3);
      {
        $output->writeListBegin(TType::STRUCT, count($this->rtimeVec));
        {
          foreach ($this->rtimeVec as $iter12)
          {
            $xfer += $iter12->write($output);
          }
        }
        $output->writeListEnd();
      }
      $xfer += $output->writeFieldEnd();
    }
    if ($this->filterDataVec !== null) {
      if (!is_array($this->filterDataVec)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('filterDataVec', TType::LST, 4);
      {
        $output->writeListBegin(TType::STRUCT, count($this->filterDataVec));
        {
          foreach ($this->filterDataVec as $iter13)
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

class MatchPoint {
  static $_TSPEC;

  /**
   * @var int
   */
  public $stopLineDistance = null;
  /**
   * @var int
   */
  public $timestamp = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'stopLineDistance',
          'type' => TType::I32,
          ),
        2 => array(
          'var' => 'timestamp',
          'type' => TType::I64,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['stopLineDistance'])) {
        $this->stopLineDistance = $vals['stopLineDistance'];
      }
      if (isset($vals['timestamp'])) {
        $this->timestamp = $vals['timestamp'];
      }
    }
  }

  public function getName() {
    return 'MatchPoint';
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
            $xfer += $input->readI32($this->stopLineDistance);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::I64) {
            $xfer += $input->readI64($this->timestamp);
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
    $xfer += $output->writeStructBegin('MatchPoint');
    if ($this->stopLineDistance !== null) {
      $xfer += $output->writeFieldBegin('stopLineDistance', TType::I32, 1);
      $xfer += $output->writeI32($this->stopLineDistance);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->timestamp !== null) {
      $xfer += $output->writeFieldBegin('timestamp', TType::I64, 2);
      $xfer += $output->writeI64($this->timestamp);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class ScatterPoint {
  static $_TSPEC;

  /**
   * @var int
   */
  public $stopDelayBefore = null;
  /**
   * @var int
   */
  public $stopLineTimestamp = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'stopDelayBefore',
          'type' => TType::I32,
          ),
        2 => array(
          'var' => 'stopLineTimestamp',
          'type' => TType::I64,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['stopDelayBefore'])) {
        $this->stopDelayBefore = $vals['stopDelayBefore'];
      }
      if (isset($vals['stopLineTimestamp'])) {
        $this->stopLineTimestamp = $vals['stopLineTimestamp'];
      }
    }
  }

  public function getName() {
    return 'ScatterPoint';
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
            $xfer += $input->readI32($this->stopDelayBefore);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::I64) {
            $xfer += $input->readI64($this->stopLineTimestamp);
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
    $xfer += $output->writeStructBegin('ScatterPoint');
    if ($this->stopDelayBefore !== null) {
      $xfer += $output->writeFieldBegin('stopDelayBefore', TType::I32, 1);
      $xfer += $output->writeI32($this->stopDelayBefore);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->stopLineTimestamp !== null) {
      $xfer += $output->writeFieldBegin('stopLineTimestamp', TType::I64, 2);
      $xfer += $output->writeI64($this->stopLineTimestamp);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class MatchTraj {
  static $_TSPEC;

  /**
   * @var \Track\ScatterPoint
   */
  public $scatterPoint = null;
  /**
   * @var string
   */
  public $flowId = null;
  /**
   * @var string
   */
  public $mapVersion = null;
  /**
   * @var int
   */
  public $x = null;
  /**
   * @var int
   */
  public $y = null;
  /**
   * @var \Track\MatchPoint[]
   */
  public $points = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'scatterPoint',
          'type' => TType::STRUCT,
          'class' => '\Track\ScatterPoint',
          ),
        2 => array(
          'var' => 'flowId',
          'type' => TType::STRING,
          ),
        3 => array(
          'var' => 'mapVersion',
          'type' => TType::STRING,
          ),
        4 => array(
          'var' => 'x',
          'type' => TType::I32,
          ),
        5 => array(
          'var' => 'y',
          'type' => TType::I32,
          ),
        6 => array(
          'var' => 'points',
          'type' => TType::LST,
          'etype' => TType::STRUCT,
          'elem' => array(
            'type' => TType::STRUCT,
            'class' => '\Track\MatchPoint',
            ),
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['scatterPoint'])) {
        $this->scatterPoint = $vals['scatterPoint'];
      }
      if (isset($vals['flowId'])) {
        $this->flowId = $vals['flowId'];
      }
      if (isset($vals['mapVersion'])) {
        $this->mapVersion = $vals['mapVersion'];
      }
      if (isset($vals['x'])) {
        $this->x = $vals['x'];
      }
      if (isset($vals['y'])) {
        $this->y = $vals['y'];
      }
      if (isset($vals['points'])) {
        $this->points = $vals['points'];
      }
    }
  }

  public function getName() {
    return 'MatchTraj';
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
            $this->scatterPoint = new \Track\ScatterPoint();
            $xfer += $this->scatterPoint->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->flowId);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 3:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->mapVersion);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 4:
          if ($ftype == TType::I32) {
            $xfer += $input->readI32($this->x);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 5:
          if ($ftype == TType::I32) {
            $xfer += $input->readI32($this->y);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 6:
          if ($ftype == TType::LST) {
            $this->points = array();
            $_size14 = 0;
            $_etype17 = 0;
            $xfer += $input->readListBegin($_etype17, $_size14);
            for ($_i18 = 0; $_i18 < $_size14; ++$_i18)
            {
              $elem19 = null;
              $elem19 = new \Track\MatchPoint();
              $xfer += $elem19->read($input);
              $this->points []= $elem19;
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
    $xfer += $output->writeStructBegin('MatchTraj');
    if ($this->scatterPoint !== null) {
      if (!is_object($this->scatterPoint)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('scatterPoint', TType::STRUCT, 1);
      $xfer += $this->scatterPoint->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->flowId !== null) {
      $xfer += $output->writeFieldBegin('flowId', TType::STRING, 2);
      $xfer += $output->writeString($this->flowId);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->mapVersion !== null) {
      $xfer += $output->writeFieldBegin('mapVersion', TType::STRING, 3);
      $xfer += $output->writeString($this->mapVersion);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->x !== null) {
      $xfer += $output->writeFieldBegin('x', TType::I32, 4);
      $xfer += $output->writeI32($this->x);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->y !== null) {
      $xfer += $output->writeFieldBegin('y', TType::I32, 5);
      $xfer += $output->writeI32($this->y);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->points !== null) {
      if (!is_array($this->points)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('points', TType::LST, 6);
      {
        $output->writeListBegin(TType::STRUCT, count($this->points));
        {
          foreach ($this->points as $iter20)
          {
            $xfer += $iter20->write($output);
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

class ScatterResponse {
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
   * @var int
   */
  public $size = null;
  /**
   * @var \Track\ScatterPoint[]
   */
  public $scatterPoints = null;

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
          'var' => 'size',
          'type' => TType::I32,
          ),
        4 => array(
          'var' => 'scatterPoints',
          'type' => TType::LST,
          'etype' => TType::STRUCT,
          'elem' => array(
            'type' => TType::STRUCT,
            'class' => '\Track\ScatterPoint',
            ),
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
      if (isset($vals['size'])) {
        $this->size = $vals['size'];
      }
      if (isset($vals['scatterPoints'])) {
        $this->scatterPoints = $vals['scatterPoints'];
      }
    }
  }

  public function getName() {
    return 'ScatterResponse';
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
          if ($ftype == TType::I32) {
            $xfer += $input->readI32($this->size);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 4:
          if ($ftype == TType::LST) {
            $this->scatterPoints = array();
            $_size21 = 0;
            $_etype24 = 0;
            $xfer += $input->readListBegin($_etype24, $_size21);
            for ($_i25 = 0; $_i25 < $_size21; ++$_i25)
            {
              $elem26 = null;
              $elem26 = new \Track\ScatterPoint();
              $xfer += $elem26->read($input);
              $this->scatterPoints []= $elem26;
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
    $xfer += $output->writeStructBegin('ScatterResponse');
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
    if ($this->size !== null) {
      $xfer += $output->writeFieldBegin('size', TType::I32, 3);
      $xfer += $output->writeI32($this->size);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->scatterPoints !== null) {
      if (!is_array($this->scatterPoints)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('scatterPoints', TType::LST, 4);
      {
        $output->writeListBegin(TType::STRUCT, count($this->scatterPoints));
        {
          foreach ($this->scatterPoints as $iter27)
          {
            $xfer += $iter27->write($output);
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

class SpaceTimeResponse {
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
   * @var int
   */
  public $size = null;
  /**
   * @var (\Track\MatchPoint[])[]
   */
  public $matchPoints = null;

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
          'var' => 'size',
          'type' => TType::I32,
          ),
        4 => array(
          'var' => 'matchPoints',
          'type' => TType::LST,
          'etype' => TType::LST,
          'elem' => array(
            'type' => TType::LST,
            'etype' => TType::STRUCT,
            'elem' => array(
              'type' => TType::STRUCT,
              'class' => '\Track\MatchPoint',
              ),
            ),
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
      if (isset($vals['size'])) {
        $this->size = $vals['size'];
      }
      if (isset($vals['matchPoints'])) {
        $this->matchPoints = $vals['matchPoints'];
      }
    }
  }

  public function getName() {
    return 'SpaceTimeResponse';
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
          if ($ftype == TType::I32) {
            $xfer += $input->readI32($this->size);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 4:
          if ($ftype == TType::LST) {
            $this->matchPoints = array();
            $_size28 = 0;
            $_etype31 = 0;
            $xfer += $input->readListBegin($_etype31, $_size28);
            for ($_i32 = 0; $_i32 < $_size28; ++$_i32)
            {
              $elem33 = null;
              $elem33 = array();
              $_size34 = 0;
              $_etype37 = 0;
              $xfer += $input->readListBegin($_etype37, $_size34);
              for ($_i38 = 0; $_i38 < $_size34; ++$_i38)
              {
                $elem39 = null;
                $elem39 = new \Track\MatchPoint();
                $xfer += $elem39->read($input);
                $elem33 []= $elem39;
              }
              $xfer += $input->readListEnd();
              $this->matchPoints []= $elem33;
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
    $xfer += $output->writeStructBegin('SpaceTimeResponse');
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
    if ($this->size !== null) {
      $xfer += $output->writeFieldBegin('size', TType::I32, 3);
      $xfer += $output->writeI32($this->size);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->matchPoints !== null) {
      if (!is_array($this->matchPoints)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('matchPoints', TType::LST, 4);
      {
        $output->writeListBegin(TType::LST, count($this->matchPoints));
        {
          foreach ($this->matchPoints as $iter40)
          {
            {
              $output->writeListBegin(TType::STRUCT, count($iter40));
              {
                foreach ($iter40 as $iter41)
                {
                  $xfer += $iter41->write($output);
                }
              }
              $output->writeListEnd();
            }
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


