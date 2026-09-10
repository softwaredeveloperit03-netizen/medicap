import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
import { HttpClient } from '@angular/common/http';
@Component({
  selector: 'app-machattereview',
  templateUrl: './machattereview.component.html',
  styleUrls: ['./machattereview.component.css']
})
export class MachattereviewComponent implements OnInit {

  constructor(private http: HttpClient,private service: DataAccessService) { }


  ngOnInit(): void {
    this.getPlants();
  }
  datas;
  plants;
  rec_date;
rec_time;
  getPlants() {
    this.service.get('hrDepartment1.php?type=get_att_data').subscribe(response => {
   this.plants = response;
 

 });

}


save(){
  let temp={}
  // temp['records']=this.combinedRecords
  this.service.post('hr/attendance.php?type=saveMachineAttendence',JSON.stringify(temp)).subscribe(response => {
    if (response['status'] == 'success') {
      alertify.success('Records saved successfully');
     
    } else {
      alertify.error('Failed: An error occured, please try again!');
    }
  });
}

}
