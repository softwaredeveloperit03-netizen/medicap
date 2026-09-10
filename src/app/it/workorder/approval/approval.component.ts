import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

  isView = false;
  results;

   constructor(private service:DataAccessService) {}


   ngOnInit(): void {
     this.getWorkOrderFOrCheckingINItDept();
   }



   getWorkOrderFOrCheckingINItDept() {
       this.service.get('it/it.php?type=getWorkOrderFOrCheckingINItDept').subscribe((response: any) => {
       this.results = response;
     });
   }



   selectedResult = [];
   view(index){
     this.isView =  true ;
     this.selectedResult = this.results[index];
   }

   saveCheckedByItDept(data){

     if(!data.valid){
       alertify.error("All Field Required !!!!!!!!");
       return;
     }

     let temp = data.value;
     temp['id'] = this.selectedResult['id'];

     this.service.post('it/it.php?type=saveCheckedByItDept', JSON.stringify(temp)).subscribe(response => {
       if (response['status'] == 'success') {
         alert('Saved Successfully');
         this.getWorkOrderFOrCheckingINItDept();
         this.isView = false;
         data.reset();

         } else {
         alert('Failed: An error occured, please try again!');
       }
     });
   }

 }
