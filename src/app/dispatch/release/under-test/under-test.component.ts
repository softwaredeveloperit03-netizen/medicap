import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-under-test',
  templateUrl: './under-test.component.html',
  styleUrls: ['./under-test.component.css']
})
export class UnderTestComponent implements OnInit {

  results;
  selectedResult=[];
  isView=false;


  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getPendingTestings();
  }

  getPendingTestings(){
    this.service.get('ipqc/finish.php?type=getPendingTestings').subscribe(response=>{
      this.results=response;
    });
  }

   
  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  
  }

 
}
