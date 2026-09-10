  import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;


@Component({
  selector: 'app-allot',
  templateUrl: './allot.component.html',
  styleUrls: ['./allot.component.css']
})
export class AllotComponent implements OnInit {



   isView = false;
   results;



    constructor(private service:DataAccessService) { }


    ngOnInit(): void {

      this.getUserReqForidIpAllot();

    }

    getUserReqForidIpAllot() {
        this.service.get('it/it.php?type=getUserReqForidIpAllot&dept='+localStorage.getItem('department')).subscribe((response: any) => {
        this.results = response;
      });
    }


    selectedResult = [];
    view(index){
      this.isView =  true ;
      this.selectedResult = this.results[index];
    }

  remark = '';


    allotIpID(data){

      if(!data.valid){
        alertify.error("All FIel Required !!!!!!!!");
        return;
      }

      let temp = data.value;
      temp['id']  =  this.selectedResult['id'];

      this.service.post('it/it.php?type=allotIpID', JSON.stringify(temp)).subscribe(response => {
        if (response['status'] == 'success') {
          alert('Updated Successfully');
          this.getUserReqForidIpAllot()
          this.isView = false;
          data.reset();

          } else {
          alert('Failed: An error occured, please try again!');
        }
      });
    }

  }
