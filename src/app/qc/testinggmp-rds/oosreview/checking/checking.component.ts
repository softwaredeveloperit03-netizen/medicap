import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-checking',
  templateUrl: './checking.component.html',
  styleUrls: ['./checking.component.css']
})
export class CheckingComponent implements OnInit {

  just_delay_ivest;
  oos_data ;
  selectedOos;
  isView = false;

  ooschecklist_data;

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingoos();
    this.getTestingPersons();
    this.QcEmployee1();
    }
    employees;
    getTestingPersons() {
      this.service.get('common.php?type=getTestingPersons').subscribe(response => {
        this.employees = response;
      });
    }
  getPendingoos() {
    this.service.get('qc/oos.php?type=getoos_forchecking').subscribe(response => {
      this.oos_data = response;
    });
  }

  getooschecklist() {
    this.service.get('master/checklist.php?type=getooschecklist_forchecking').subscribe(response => {
      this.ooschecklist_data = response;
    });
  }

  QCEmployee;
  QcEmployee1() {
    
    this.service.get('master/checklist.php?type=getapprovedEmployee&department1=Quality Control').subscribe(response => {
      this.QCEmployee = response;
    });
  }
  additional_observation='';
  previous_add_observation='';
  checklistListfinal:any=[];
  viewoos(index){
    this.isView = true;

    this.selectedOos = this.oos_data[index];
    this.additional_observation=this.selectedOos['additional_observation']
    this.previous_add_observation=this.selectedOos['previous_add_observation']
    this.checklistListfinal = this.selectedOos['oos_data'];
    this.getooschecklist();
  }

  ClosedOOS;


  jadugarFunction(){

    
    this.ClosedOOS = this.selectedOos['oos_data'].concat(this.ooschecklist_data);

    console.log(this.ClosedOOS);

  }
  selected_emp=[];
  emp_ids;
  cause_find;
  supervisor='';
  cause_of_analysis;
  hypo_study=false;
  cause_analysis=false;
  SET_cause_of_analysis(value){
    this.cause_of_analysis=value; 
   }
  hypo(value){
if(value=='Proceed  Further For Hypothesis Study'){
  this.cause_of_analysis='';
  this.hypo_study=true;
  this.cause_analysis=true;
}else{
  this.cause_of_analysis='';
  this.hypo_study=false;
  this.cause_analysis=true;

}
this.just_delay_ivest=value;
  }
  GEt_emp_date(value){
    this.cause_find=value; 
   }
   repeat_analysis;
   GEt_repeat_analysis(value){
    this.repeat_analysis=value; 
   }
   Hypothesis_study;
   GEt_Hypothesis_study(value){
    this.Hypothesis_study=value; 
   }
   info_concern_contor;
   GEt_info_concern_contor(value){
    this.info_concern_contor=value; 
   }
  sendreview(){

    console.log("this.oos_data");
    console.log( this.selectedOos['oos_data']);
    console.log("this.ooschecklist_data");
    console.log( this.ooschecklist_data);


    // this.jadugarFunction();




  // this.selectedOos['ClosedOOS'] = this.ClosedOOS;
   this.selectedOos['supervisor'] = this.emp_ids;
   this.selectedOos['just_delay_ivest'] = this.just_delay_ivest;
   this.selectedOos['cause_find'] = this.cause_find;
   this.selectedOos['repeat_analysis'] = this.repeat_analysis;
   this.selectedOos['Hypothesis_study'] = this.Hypothesis_study;
   this.selectedOos['cause_of_analysis'] = this.cause_of_analysis;
   this.selectedOos['info_concern_contor'] = this.info_concern_contor;

        
    this.service.post('qc/oos.php?type=oosreview&oosid='+this.selectedOos['id']+'&supervisor='+ this.emp_ids, JSON.stringify(this.selectedOos)).subscribe(response => {
      if (response['status'] == "success") {
         this.isView = false;
         alertify.success("submit sucessfully");
        this.getPendingoos();
      } else {
        alertify.error('An error occured');
      }
    });

  }
  








  isdeffbtn =false;

  furtherdiss(mainIndex, subIndex, value){
 
    if(value == 'NO'){
      this.isdeffbtn = true;
      
      try {
          if (this.ooschecklist_data[mainIndex] && Array.isArray(this.ooschecklist_data[mainIndex].check_points)) {
            this.ooschecklist_data[mainIndex].check_points = this.ooschecklist_data[mainIndex].check_points.slice(0, subIndex);
          } else {
            throw new Error("Invalid mainIndex or check_points is not an array.");
          }
          if (mainIndex >= 0 && mainIndex < this.ooschecklist_data.length) {
            mainIndex = mainIndex + 1;
            this.ooschecklist_data = this.ooschecklist_data.slice(0, mainIndex);
          } else {
            throw new Error("Invalid mainIndex.");
          }
      } catch (error) {
          console.error("An error occurred:", error);
      }
    }else{
      
    }

  console.log( this.ooschecklist_data);

}


}
