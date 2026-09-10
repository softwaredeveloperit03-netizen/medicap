import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  isView = false;
  results;

   constructor(private service:DataAccessService) {}


   ngOnInit(): void {
     this.getWorkOrderLog();
   }


   getWorkOrderLog() {
       this.service.get('it/it.php?type=getWorkOrderLog').subscribe((response: any) => {
       this.results = response;
     });
   }

   selectedResult = [];
   view(index){
     this.isView =  true ;
     this.selectedResult = this.results[index];
   }


 }
