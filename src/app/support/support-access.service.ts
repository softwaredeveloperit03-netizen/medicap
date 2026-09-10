import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { BehaviorSubject } from 'rxjs';
import { Router } from '@angular/router';
declare let alertify;


@Injectable({
  providedIn: 'root'
})
export class SupportAccessService {

  [x: string]: any;

  apiHost = window.location.hostname;

   domain = 'https://aurenyxgmp.com/admin/api/';  
 
 
  public url = this.domain;

  username = localStorage.getItem('username');
  constructor(private http: HttpClient, private router: Router) {
    this.username = localStorage.getItem('username');
 
  }


  current_route: string;



  get(url) {
    this.current_route = this.router.url.replace('/', '-'); 
    url =this.url +url +'&description=' +this.current_route +'&token=' +localStorage.getItem('token') +'&user_no=gmpdemo1' +'&plant_id=' +localStorage.getItem('plant_id')+'&raisedByName=' +localStorage.getItem('username') +'&raisedById=' +localStorage.getItem('loger_id');
    return this.http.get(url);
  }


  post(url, postData) {
    this.current_route = this.router.url.replace('/', '-');
    url = this.url + url + '&description=' + this.current_route + '&token=' + localStorage.getItem('token') + '&user_no=gmpdemo1' + '&plant_id=' +localStorage.getItem('plant_id')+'&raisedByName=' +localStorage.getItem('username') +'&raisedById=' +localStorage.getItem('loger_id');
    return this.http.post(url, postData);
  }

 

  getPlantConfigFields(field_type) {
    let value = '';
    let data = localStorage.getItem('client_info');
    let json_data = JSON.parse(data);
    if (json_data == null || json_data == '') {
      return null;
    }
    switch (field_type) {
      case 'plant_type':
        value = json_data[0]['plant_type'];
        break;
      case 'software_license_no':
        value = json_data[0]['software_license_no'];
        break;
      case 'software_type':
        value = json_data[0]['software_type'];
        break;
      case 'plant_name':
        value = json_data[0]['unit_name'];
        break;
      case 'plant_id':
        value = localStorage.getItem('plant_id');
        break;
      case 'main_client_code':
        value = this.main_client_code;
      case 'plant_logo':
        value = json_data[0]['logo_rect'];
    }
    return value;
  }








 
}
