import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-receive',
  templateUrl: './receive.component.html',
  styleUrls: ['./receive.component.css']
})
export class ReceiveComponent implements OnInit {

   isView = false;
   results;



    constructor(private service:DataAccessService) { }


    ngOnInit(): void {

      this.getUserReqForItDeptComment();

    }

    getUserReqForItDeptComment() {
        this.service.get('it/it.php?type=getUserReqForItDeptComment&dept='+localStorage.getItem('department')).subscribe((response: any) => {
        this.results = response;
      });
    }


    selectedResult = [];
    view(index){
      this.isView =  true ;
      this.selectedResult = this.results[index];
    }

  remark = '';


    saveItComment(data){

      if(!data.valid){
        alertify.error("All FIel Required !!!!!!!!");
        return;
      }

      let temp = data.value;
      temp['id']  =  this.selectedResult['id'];

      this.service.post('it/it.php?type=saveItComment', JSON.stringify(temp)).subscribe(response => {
        if (response['status'] == 'success') {
          alert('Updated Successfully');
          this.getUserReqForItDeptComment()
          this.isView = false;
          data.reset();

          } else {
          alert('Failed: An error occured, please try again!');
        }
      });
    }

  }
