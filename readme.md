# itstool 

这个项目是信控接口项目，所有与前端交互的接口都写在这个项目中，由这个项目进行后端服务和整合和交互。

项目适应CI框架，采用MSC结构

* controller层可以调用service和model层
* service层封装比较通用的业务逻辑
* model层负责和db或者第三方服务的交互

本项目基本没有view层。直接返回json结构。