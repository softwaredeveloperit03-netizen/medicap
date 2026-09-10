import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-evaluation',
  templateUrl: './evaluation.component.html',
  styleUrls: ['./evaluation.component.css']
})
export class EvaluationComponent implements OnInit {

  results;
  selectedResult=[];
  isView=false;
  
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getPendingRecommendations();
  }

  getPendingRecommendations(){
    this.service.get('qms/deviation.php?type=getPendingEvaluations').subscribe(response=>{
      this.results=response;
    });
  }

  viewfile(link){
    window.open(this.service.url + '../../upload/deviation/' + link);
  }


  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }
 
  save(data){
    let temp=data.value;
    temp['deviation_no']=this.selectedResult['deviation_no'];
     this.service.post('qms/deviation.php?type=saveEvaluation&id=' +this.selectedResult['id'],JSON.stringify(temp)).subscribe(response => {
      if(response['status'] == 'success'){
        alertify.success('Data Save Successfully!');
        this.isView = false;
        this.getPendingRecommendations();
      }else{
        alertify.error('Failed an error occured,please try again!');
      }
    });
  }
}
