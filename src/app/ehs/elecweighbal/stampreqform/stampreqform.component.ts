import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-stampreqform',
  templateUrl: './stampreqform.component.html',
  styleUrls: ['./stampreqform.component.css']
})
export class StampreqformComponent implements OnInit {

  stpRequest;
  stampingArr;
  //isNew = false;
  selectedReq = [];

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    //this.getstpRequest();
    this.GET_InvolvedPersons();
    this.getDepartments();
     this.getSection();
  }

  // getstpRequest(){
  //   this.service.get('common.php?type=getstpRequest').subscribe(response =>{
  //       this.stpRequest =response
  //     });
  // }

    selectedEmp=[];
    getEmpdata(i){

      this.selectedEmp=this.InvolvedPersons[i-1];
    }
  Sections;
    getSection() {
    this.service
      .get('master/section.php?type=getSection')
      .subscribe((response) => {
        this.Sections = response;
      });
  }

   InvolvedPersons: any = [];
  GET_InvolvedPersons() {
    this.service
      .get('common.php?type=AllEmployeeList')
      .subscribe((response) => {
        this.InvolvedPersons = response;
      });
  }

  departments;
   getDepartments() {
    this.service.get('common.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }

  isNew;
    saveStampingRequest(data){
    let temp = data.value;
    console.log(temp);
    this.service.post('ehs/electronicWeightingBalance/stampingrequest.php?type=saveStampingRequest', JSON.stringify(temp)).subscribe((response) => {
        if (response['status'] == 'success') {
          alertify.success('Record Save Successfully');
            this.isNew=false;
        
        } else {
          alertify.error(response['status']);
        }
      });
  }

}
