import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
import { HttpClient } from '@angular/common/http';
@Component({
  selector: 'app-machatt',
  templateUrl: './machatt.component.html',
  styleUrls: ['./machatt.component.css']
})
export class MachattComponent implements OnInit {

  constructor(private http: HttpClient,private service: DataAccessService) { }


  ngOnInit(): void {
    this.getPlants();
  }
  datas;
  plants;
  rec_date;
rec_time;
  getPlants() {
    this.http.get('http://103.240.91.68:9096/records').subscribe(response => {
   this.plants = response;
   this.datas=this.plants.extend.records
   const recordMap = new Map<string, Map<string, any>>();
   for (let i = 0; i < this.datas.length; i++) {

    const punchString = this.datas[i].punch;

    // Ensure punchString is valid before splittingre
    if (punchString) {
      const punchParts = punchString.split(' ');
      if (punchParts.length >= 2) {
        const punchDate = punchParts[0]; // "2024-06-28"
        const punchTime = punchParts[1] + ' ' + punchParts[2]; // "19:33 PM"
        
        // Assign punch date and time
        this.datas[i].entry_date = punchDate;
        this.datas[i].entry_time = punchTime;
      }
    }


  }

 });

}
from_date: string;
to_date: string;
combinedRecords: any[] = [];
getlAttendace() {
  // Ensure from_date and to_date are set
  if (this.from_date && this.to_date) {
    // Parse date strings into Date objects
    const fromDate = new Date(this.from_date);
    const toDate = new Date(this.to_date);
    
    // Filter records within the date range
    this.combinedRecords = this.datas.filter(record => {
      const recordDate = new Date(record.entry_date);
      return recordDate >= fromDate && recordDate <= toDate;
    });
  }


  console.log('this.combinedRecords :>> ', this.combinedRecords);
}


save(){
  let temp={}
  temp['records']=this.combinedRecords
  this.service.post('hr/attendance.php?type=saveMachineAttendence',JSON.stringify(temp)).subscribe(response => {
    if (response['status'] == 'success') {
      alertify.success('Records saved successfully');
     
    } else {
      alertify.error('Failed: An error occured, please try again!');
    }
  });
}

}
