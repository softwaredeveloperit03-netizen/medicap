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

  ngOnInit() {
    this.getInprocessTestings();
  }

  getInprocessTestings(){
    this.service.get('ipqc/finish.php?type=getInprocessTestings').subscribe(response=>{
      this.results=response;
    });
  }

   
  view(index){
    this.selectedResult=this.results[index];
    this.isView=true; 
  }

  approveTesting(status){
    this.service.get('ipqc/finish.php?type=approveTesting&id='+this.selectedResult['id']+'&status='+ status).subscribe(response=>{
      if(response['status']=='success'){
        alertify.success('data Successfuly updated');
        this.isView=false;
        this.getInprocessTestings();
      }else{
        alertify.error('Some error occured!');
      }
    });
  }

}
