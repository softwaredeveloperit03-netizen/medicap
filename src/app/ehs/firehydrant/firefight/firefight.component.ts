import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-firefight',
  templateUrl: './firefight.component.html',
  styleUrls: ['./firefight.component.css'],
})
export class FirefightComponent implements OnInit {
  //stpfire;
  selectedData = [];
  
  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
    this.GET_InvolvedPersons();
    this.getstpfire();
  } 
   InvolvedPersons: any = [];
  GET_InvolvedPersons() {
    this.service
      .get('common.php?type=AllEmployeeList')
      .subscribe((response) => {
        this.InvolvedPersons = response;
      });
  }
  selectedEmp=[];
  getEmpdata(i){
    this.selectedEmp=this.InvolvedPersons[i-1]
  }

stpfire;
  getstpfire(){
    this.service.get('ehs/fire.php?type=getsaveFighterList').subscribe(response =>{
        this.stpfire =response
      });
  }

  FighterList=[]
  addfire(data) {
    let temp = data.value;
   this.FighterList[this.FighterList.length]=temp
  }

  submitfireform(data){
    let temp = {}
    temp['FighterList']=this.FighterList;
    console.log(temp);
 
     this.service.post(
        'ehs/fire.php?type=saveFighterList',JSON.stringify(temp))
      .subscribe((response) => {
        if (response['status'] == 'success') {
          alertify.success('Record Save Successfully');
          this.getstpfire();
        } else {
          alertify.error(response['status']);
        }
      });
  }
}
