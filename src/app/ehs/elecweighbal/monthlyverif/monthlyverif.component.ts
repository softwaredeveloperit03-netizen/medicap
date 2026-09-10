import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;

@Component({
  selector: 'app-monthlyverif',
  templateUrl: './monthlyverif.component.html',
  styleUrls: ['./monthlyverif.component.css']
})
export class MonthlyverifComponent implements OnInit {

  verifChk;
  // isNew = false;

  selectedVerif = [];
  
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    // this.getverifChk();
    this.GET_InvolvedPersons();
    this.getSection();
  }

  // getverifChk(){
  //   this.service.get('common.php/type?=getVerifChk').subscribe(response => {
  //     this.verifChk = response;
  //   });
  // } 

  Sections;

  getSection() {
    this.service
      .get('master/section.php?type=getSection')
      .subscribe((response) => {
        this.Sections = response;
      });
  }
   
  selectedEmp=[];
    getEmpdata(i){

      this.selectedEmp=this.InvolvedPersons[i-1];
    }

   InvolvedPersons: any = [];
  GET_InvolvedPersons() {
    this.service
      .get('common.php?type=AllEmployeeList')
      .subscribe((response) => {
        this.InvolvedPersons = response;
      });
  }

  isNew;
    saveMonthlyVerif(data){
   let temp = data.value;
    console.log(temp);
  this.service.post('ehs/electronicWeightingBalance/monthlyverification.php?type=saveMonthlyVerifRecord', JSON.stringify(temp)).subscribe((response) => {
        if (response['status'] == 'success') {
          alertify.success('Record Save Successfully');
            this.isNew=false;
        
        } else {
          alertify.error(response['status']);
        }
      });
  }
 
}
