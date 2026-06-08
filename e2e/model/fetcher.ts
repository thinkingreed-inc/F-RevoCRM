import axios from "axios";
import { FRDescribeFieldsType, FRDescribeType, FRResponse, FRRetrieveItems } from "./types/frBase";
import md5 from "md5";

export const baseUrl = "http://localhost/webservice.php";

export const client = axios.create({
  baseURL: baseUrl, // ここでベースURLを設定します
});

/**
 * 認証を行うためのChallengeTokenを取得する
 */
export const getChallengeToken = async (username: string) => {
  const response = await client.get<
    FRResponse<{
      token: string;
      serverTime: number;
      expireTime: number;
    }>
  >(`?operation=getchallenge&username=${username}`);
  if (response.data.success === false) {
    return false;
  }

  return response.data.result.token;
};

/**
 * F-RevoCRMにAPIでログインする処理
 * アクセストークンを返す
 */
export const login = async (username: string, accessKey: string) => {
  const challengeToken = await getChallengeToken(username);
  if (!challengeToken) {
    return false;
  }

  const formData = new FormData();
  formData.append("operation", "login");
  formData.append("username", username);
  formData.append("accessKey", md5(`${challengeToken}${accessKey}`));

  const response = await client.post<
    FRResponse<{
      username: string;
      first_name: string;
      last_name: string;
      email: string;
      time_zone: string;
      hour_format: string;
      date_format: string;
      is_admin: string;
      call_duration: string;
      other_event_duration: string;
      sessionName: string;
      userId: string;
      version: string;
      vtigerVersion: string;
    }>
  >("", formData);

  if (response.data.success === false) {
    return false;
  }

  return response.data.result;
};

export const frgetListTypes = async (sessionName: string) => {
  const response = await client.get<
    FRResponse<any>
  >(`?operation=listtypes&fieldTypeList=&sessionName=${sessionName}`);

  if (response.data.success === false) {
    return false;
  }
  return response.data.result;
};


export const frgetDescribe = async (sessionName: string, moduleName: string) => {
  const response = await client.get<
    FRResponse<FRDescribeType>
  >(`?operation=describe&elementType=${moduleName}&sessionName=${sessionName}`);

  if (response.data.success === false) {
    return false;
  }
  return response.data.result;
};

export const frgetOneRecord = async (sessionName: string, moduleName: string) => {
  const response = await client.get<
    FRResponse<FRRetrieveItems[]>
  >(`?operation=query&query=SELECT * FROM ${moduleName} ORDER BY modifiedtime desc LIMIT 1;&sessionName=${sessionName}`);

  if (response.data.success === false) {
    return false;
  }
  return response.data.result;
};