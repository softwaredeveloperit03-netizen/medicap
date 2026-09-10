import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-checking',
  templateUrl: './checking.component.html',
  styleUrls: ['./checking.component.css']
})
export class CheckingComponent implements OnInit {

  results;
  selectedResult=[];
  isView=false;

  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getPendingDeptChecking();
  }

  getPendingDeptChecking(){
    this.service.get('qms/deviation.php?type=getPendingDeptChecking').subscribe(response=>{
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

  update(status){
    this.service.get('qms/deviation.php?type=checkDeptDeviation&id='+this.selectedResult['id']+'&deviation_no=' +this.selectedResult['deviation_no']+'&status='+status).subscribe(response=>{
      if(response['status']=='success'){
        alertify.success('Deviation checking Successfuly');
        this.isView=false;
        this.getPendingDeptChecking();
      }else{
        alertify.error('some error occured!');
      }
    });
  }

}
