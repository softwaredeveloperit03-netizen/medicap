import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify;

@Component({
  selector: 'app-assessment',
  templateUrl: './assessment.component.html',
  styleUrls: ['./assessment.component.css']
})
export class AssessmentComponent implements OnInit {

  results;
  selectedResult=[];
  isView=false;
  isyes=false;
  isyesroot=false;
  isyesrootcause=false;
  isyesdev=false;
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getPendingQAVerification();
  }

  getPendingQAVerification(){
    this.service.get('qms/deviation.php?type=getPendingAssessments').subscribe(response=>{
      this.results=response;
    });
  }

  viewfile(link){
    window.open(this.service.url + '../../upload/deviation/' + link);
  }
  impact;

  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
    if(this.selectedResult['quality_impact']!=''){
          this.impact='Available';
    }else{
      this.impact='Not Available';
    }
    console.log(this.impact)
  }

  getAction(value){
    if(value=='YES'){
      this.isyes=true;
    }else{
      this.isyes=false;
    }
  }

  getRoot(value){
    if(value=='YES'){
      this.isyesroot=true;
    }else{
      this.isyesroot=false;
    }
  }

  getRootcause(value){
    if(value=='YES'){
      this.isyesrootcause=true;
    }else{
      this.isyesrootcause=false;
    }
  }

  getDeviationAction(value){
    if(value=='YES'){
      this.isyesdev=true;
    }else{
      this.isyesdev=false;
    }
  }

  save(data){
    let temp=data.value;
    temp['deviation_no']=this.selectedResult['deviation_no'];
     this.service.post('qms/deviation.php?type=saveAssessment&id=' +this.selectedResult['id']+'&impact='+this.impact,JSON.stringify(temp)).subscribe(response => {
      if(response['status'] == 'success'){
        alertify.success('Data Save Successfully!');
        this.isView = false;
        this.getPendingQAVerification();
      }else{
        alertify.error('Failed an error occured,please try again!');
      }
    });
  }



}
