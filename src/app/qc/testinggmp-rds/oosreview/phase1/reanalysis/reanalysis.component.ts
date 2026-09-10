import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-reanalysis',
  templateUrl: './reanalysis.component.html',
  styleUrls: ['./reanalysis.component.css']
})
export class ReanalysisComponent implements OnInit {
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

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingoos();
    this.QcEmployee1();
    }
    start_time_new ;
    end_time_new ;
    start_time_new1 ;
    end_time_new1 ;
    getCurrentTime(action) {
    
      var d = new Date(),
      year = d.getFullYear(),
      month = ((d.getMonth() + 1) < 10 ? '0' : '') + (d.getMonth() + 1),
      day = (d.getDate() < 10 ? '0' : '') + d.getDate(),
      h = (d.getHours() < 10 ? '0' : '') + d.getHours(),
      m = (d.getMinutes() < 10 ? '0' : '') + d.getMinutes();
  
      if (action == 'from_time') {
          this.start_time_new = day + '-' + month + '-' + year + ' ' + h + ':' + m;
          this.start_time_new1 =  h + ':' + m;
      } else {
          this.end_time_new = day + '-' + month + '-' + year + ' ' + h + ':' + m;
          this.end_time_new1 =  h + ':' + m;
      }
      
    }
    result_new;
observation_new;
remark_new;
    reanalysisList=[];
    submitedit(data) {
      if (!data.valid) {
        alert('All fields are required');
        return;
      }
      let temp  = data.value;
      temp['end_time_new'] = this.end_time_new;
      temp['start_time_new'] = this.start_time_new;
      // temp['remark_new'] = temp['remark_new'];
      // temp['observation_new'] = temp['observation_new'];
      // temp['result_new'] = temp['result_new'];
  
      this.reanalysisList[this.reanalysisList.length]=temp;

      console.log('this.reanalysisList :>> ', this.reanalysisList);

      this.isOOS=false;


      
    }
    isOOS=false;
    editform() {
    
      // this.selectedTest['grade'] = this.selectedTesting['grade'];
      // this.selectedTest['testing_no'] = this.selectedTesting['testing_no'];
      // this.selectedTest['sampling_no'] = this.selectedTesting['sampling_no'];
      // this.selectedTest['specification_no'] = this.selectedTesting['specification_no'];
      // this.selectedTest['material_code'] = this.selectedTesting['material_code'];
      // this.selectedTest['material_name'] = this.selectedTesting['material_name'];
      // this.selectedTest['batch_no'] = this.selectedTesting['batch_no'];
      this.isOOS = true;
      // console.log(this.selectedTest);
    }
  getPendingoos() {
    this.service.get('qc/oos.php?type=getoosforreview11').subscribe(response => {
      this.oos_data = response;
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
  selected_reanalysis1=[];
  selected_reanalysis2=[];
  selected_reanalysis3=[];
  selected_reanalysis4=[];
  selected_reanalysis5=[];
  selected_reanalysis6=[];
  selected_reanalysis7=[];
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
  this.selectedOos['analysis_count'] = this.selectedOos['analysis_count'];
  this.selectedOos['reanalysisList'] = this.reanalysisList;
  this.selectedOos['observation_new'] = this.observation_new;
  this.selectedOos['hypo_cause_find']= this.selectedOos['hypo_cause_find']

        
    this.service.post('qc/oos.php?type=closedoos&oosid='+this.selectedOos['id'], JSON.stringify(this.selectedOos)).subscribe(response => {
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
