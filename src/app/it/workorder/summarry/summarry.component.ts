import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-summarry',
  templateUrl: './summarry.component.html',
  styleUrls: ['./summarry.component.css']
})
export class SummarryComponent implements OnInit {

  isView = false;
  results;



   constructor(private service:DataAccessService) {}


   ngOnInit(): void {
     this.getWorkOrderForSummaryOfWorkDone();
   }



   getWorkOrderForSummaryOfWorkDone() {
       this.service.get('it/it.php?type=getWorkOrderForSummaryOfWorkDone').subscribe((response: any) => {
       this.results = response;
     });
   }



   selectedResult = [];
   view(index){
     this.isView =  true ;
     this.selectedResult = this.results[index];
   }

   saveWorkOrderSummary(data){

     if(!data.valid){
       alertify.error("All Field Required !!!!!!!!");
       return;
     }

     let temp = data.value;
     temp['id'] = this.selectedResult['id'];

     this.service.post('it/it.php?type=saveWorkOrderSummary', JSON.stringify(temp)).subscribe(response => {
       if (response['status'] == 'success') {
         alert('Saved Successfully');
         this.getWorkOrderForSummaryOfWorkDone();
         this.isView = false;
         data.reset();

         } else {
         alert('Failed: An error occured, please try again!');
       }
     });
   }

 }
