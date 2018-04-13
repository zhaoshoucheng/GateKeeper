namespace java io.didichuxing.sts.data.thrift.mtraj

struct Rtime {
    1:string mapVersion;
    2:i64    startTS;
    3:i64    endTS;
}

struct Request {
    1:string       junctionId;
    2:string       flowId;
    3:list<Rtime>  rtimeVec;
    4:double       x;
    5:double       y;
    6:i32          num;
}

struct MatchPoint {
    1:i32 stopLineDistance;
    2:i64 timestamp;
}

struct ScatterPoint {
    1:i32 stopDelayBefore;
    2:i64 stopLineTimestamp;
}

struct MatchTraj {
    1:ScatterPoint scatterPoint;
    2:string flowId;
    3:string mapVersion;
    4:i32 x;
    5:i32 y;
    6:list<MatchPoint> points;
}

struct ScatterResponse {
    1:list<ScatterPoint> scatterPoints;
}

struct SpaceTimeResponse {
    1:list<list<MatchPoint>> matchPoints;
}

service MtrajService {

    /*
     * 获取散点图匹配点轨迹
     */
    ScatterResponse getScatterMtraj(1: Request request)

    /*
     * 获取时空图匹配点轨迹
     */
    SpaceTimeResponse getSpaceTimeMtraj(1: Request request)
	
}
