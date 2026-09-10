import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { HttpClient } from '@angular/common/http';
declare let alertify;

@Component({
  selector: 'app-hypo',
  templateUrl: './hypo.component.html',
  styleUrls: ['./hypo.component.css']
})
export class HypoComponent implements OnInit {
  oos_data ;
  selectedOos;
  isView = false;

  ooschecklist_data;

  found_conc=[
    {"param":" If Yes,then proceed for repeat analysis using same sample by the same analyst in presence of second analyst / section head by correcting the error"}
  ];
  not_found_conc=[
    {"param":" If no, then proceed for Hypothesis study to indentify the root cause"}
  ];

  constructor(private http: HttpClient,private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingoos();
    this.QcEmployee1();
    // this.getPlants();

    }
    plants;
    getPlants() {
         this.http.get('http://103.240.91.68:9096/records').subscribe(response => {
        this.plants = response;
      
      });
  
    }
    selected_reanalysis1=[];
    selected_reanalysis2=[];
    selected_reanalysis3=[];
    selected_reanalysis4=[];
    selected_reanalysis5=[];
    selected_reanalysis6=[];
    selected_reanalysis7=[];
  getPendingoos() {
    this.service.get('qc/oos.php?type=getoosforreview1').subscribe(response => {
      this.oos_data = response;
      

      console.log('this.selected_reanalysis1 :>> ', this.selected_reanalysis1);
    });
  }

  getooschecklist() {
    this.service.get('master/checklist.php?type=getooschecklis_for_review').subscribe(response => {
      this.ooschecklist_data = response;
    });
  }

  QCEmployee;
  QcEmployee1() {
    
    this.service.get('master/checklist.php?type=getapprovedEmployee&department1=Quality Control').subscribe(response => {
      this.QCEmployee = response;
    });
  }

  checklistListfinal:any=[];
  reanalysis_observation:any=[];
  reanalysis_result:any=[];
  reanalysis_remark:any=[];
  reanalysis_limit_type:any=[];
  reanalysis_limit:any=[];
  viewoos(index){
    this.isView = true;

    this.selectedOos = this.oos_data[index];
    this.checklistListfinal = this.selectedOos['oos_data'];
    this.reanalysis_observation=this.selectedOos['reanlysis_data'][0]['observation']
    this.reanalysis_result=this.selectedOos['reanlysis_data'][0]['result']
    this.reanalysis_remark=this.selectedOos['reanlysis_data'][0]['remark']
    this.reanalysis_remark=this.selectedOos['reanlysis_data'][0]['remark']
    this.reanalysis_limit_type=this.selectedOos['reanlysis_data'][0]['limit_type']
     this.reanalysis_limit=this.selectedOos['reanlysis_data'][0]['limit']
     this.selected_reanalysis1=this.selectedOos['reanalysis1'][0];
     this.selected_reanalysis2=this.selectedOos['reanalysis2'][0];
     this.selected_reanalysis3=this.selectedOos['reanalysis3'][0];
     this.selected_reanalysis4=this.selectedOos['reanalysis4'][0];
     this.selected_reanalysis5=this.selectedOos['reanalysis5'][0];
     this.selected_reanalysis6=this.selectedOos['reanalysis6'][0];
     this.selected_reanalysis7=this.selectedOos['reanalysis7'][0];
  }
  

  ClosedOOS;


  jadugarFunction(){

    
    this.ClosedOOS = this.selectedOos['oos_data'].concat(this.ooschecklist_data);

    console.log(this.ClosedOOS);

  }


  cause_find;
  hypo_cause_find;
  sampling_error;

  closeoos(){

    console.log("this.oos_data");
    console.log( this.selectedOos['oos_data']);
    console.log("this.ooschecklist_data");
    console.log( this.ooschecklist_data);


    // this.jadugarFunction();




  // this.selectedOos['ClosedOOS'] = this.ClosedOOS;
  this.selectedOos['cause_find'] = this.cause_find;
  this.selectedOos['result_for_htpothesis'] = this.result_for_htpothesis;
  this.selectedOos['result_of_repeat_analysis'] = this.result_of_repeat_analysis;
  this.selectedOos['result_of_repeat_analysis'] = this.result_of_repeat_analysis;
  this.selectedOos['Conclusion'] = this.Conclusion;
  this.selectedOos['corrective_action'] = this.corrective_action;
  this.selectedOos['hypo_cause_find'] = this.hypo_cause_find;
  this.selectedOos['sampling_error'] = this.sampling_error;

        
    this.service.post('qc/oos.php?type=closedoos1&oosid='+this.selectedOos['id'], JSON.stringify(this.selectedOos)).subscribe(response => {
      if (response['status'] == "success") {
         this.isView = false;
         alertify.success("submit sucessfully");
        this.getPendingoos();
      } else {
        alertify.error('An error occured');
      }
    });

  }
  result_of_repeat_analysis;
  result_for_htpothesis;
  Conclusion;
  corrective_action;
  rora(value){
    this.result_of_repeat_analysis=value; 
   }
  hypo(value){
    this.result_for_htpothesis=value; 
   }
  GEt_emp_date(value){
    this.cause_find=value; 
   }
   Conclu(value){
    this.Conclusion=value; 
   }
   correctiveaction(value){
    this.corrective_action=value; 
   }
  
}
