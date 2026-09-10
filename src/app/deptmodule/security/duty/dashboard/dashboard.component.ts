import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {

  loading;
  item;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getData();
  }
  getData() {
   
    this.service.get('admin/housekeeping.php?type=get_Outdoor_duty&status=pending').subscribe(response => {
      this.item = response;
    
    });
  }
  download(){
    
  }


  update(id,status) {
 
    let temp = {};
    temp['id']=id;
    temp['status']=status; 
    this.service.post('admin/housekeeping.php?type=update_oudate_status', JSON.stringify(temp))
    .subscribe(response => {
      if (response['status'] === 'success') {       
        this.getData();
        alertify.success("save successfully");

      } else {
        alertify.error('Please Try Again');
      }
      })
    }
}
