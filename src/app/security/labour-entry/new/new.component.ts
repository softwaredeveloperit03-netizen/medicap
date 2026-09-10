import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  todaysLabours;
  loading;
  isEntry = false;
  labour_id = 0;
  entry_time = '';
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getTodaysLabors();
  }

  addEntryRecord(labour_id) {
    this.isEntry = true;
    this.labour_id = labour_id;
  }
  getTodaysLabors() {
    this.todaysLabours =[];
    this.service.get('security/labour.php?type=getActiveLabours').subscribe((response: any) => {
      this.todaysLabours = response;
      //this.filterItem()
    });
  }

  labourEntry(labour_id,status) {   
    let obj = {
      "labour_id": labour_id,
      "status": status,
      "entry_time": null
    }
    this.service.post('security/labour.php?type=labourEntry', JSON.stringify(obj)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.savedSuccess'));

        this.getTodaysLabors();
        this.isEntry = false;
      } else {
        alertify.error('An error occured!');
      }
    });
  }
  manualEntry(status, entry_time) {
    if (entry_time == undefined || entry_time == '') {
      alertify.error('Please select Entry Time of the Labour');
      return;
    }
    let obj = {
      "labour_id": this.labour_id,
      "status": status,
      "entry_time": entry_time
    }
    this.service.post('security/labour.php?type=labourEntry', JSON.stringify(obj)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.savedSuccess'));

        this.getTodaysLabors();
        this.isEntry = false;
      } else {
        alertify.error('An error occured!');
      }
    });
  }

}


