import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-usage-record',
  templateUrl: './usage-record.component.html',
  styleUrls: ['./usage-record.component.css']
})
export class UsageRecordComponent implements OnInit {

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
this.getDetails();
  }

  data;
  getDetails()
  {
    this.service.get('qa/all2.php?type=get_procedure_log').subscribe((response:any) => {
      this.data = response;
     
    });
  }
  
  incident_location;detailed_desc;injured_person_name;incident_time;
isView=false;
selectedResult=[];
view(index){
  this.selectedResult=this.data[index]
  this.isView=true;
  this.incident_location=this.selectedResult['incident_location'];
  this.detailed_desc=this.selectedResult['detailed_desc'];
  this.injured_person_name=this.selectedResult['injured_person_name'];
  this.incident_time=this.selectedResult['incident_time']; 
}

save(data) {
  console.log(data.value);
  if (!data.valid) {
    alert('All fields are required');
    return;
  }

  this.service.post('qa/all2.php?type=save_usage_record', JSON.stringify(data.value)).subscribe(response => {
    if (response['status'] == 'success') {
      alert('Saved Successfully');
      // this.router.navigate(['/checklist']);
    } else {
      console.log(response);
      alert('Failed: An error occured, please try again!');
    }
  });
}

}
