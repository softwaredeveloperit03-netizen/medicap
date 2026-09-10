import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  isView = false;
  results;



   constructor(private service:DataAccessService) {}


   ngOnInit(): void {
     this.getWorkOrderForReceivingByIt();
   }



   getWorkOrderForReceivingByIt() {
       this.service.get('it/it.php?type=getWorkOrderForReceivingByIt').subscribe((response: any) => {
       this.results = response;
     });
   }



   selectedResult = [];
   view(index){
     this.isView =  true ;
     this.selectedResult = this.results[index];
   }

 saveReceivedBy(data){

     if(!data.valid){
       alertify.error("All Field Required !!!!!!!!");
       return;
     }

     let temp = data.value;
     temp['id'] = this.selectedResult['id'];

     this.service.post('it/it.php?type=saveReceivedBy', JSON.stringify(temp)).subscribe(response => {
       if (response['status'] == 'success') {
         alert('Saved Successfully');
         this.getWorkOrderForReceivingByIt();
         this.isView = false;
         data.reset();

         } else {
         alert('Failed: An error occured, please try again!');
       }
     });
   }

 }
