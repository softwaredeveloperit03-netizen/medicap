import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-cleaninglog',
  templateUrl: './cleaninglog.component.html',
 })
export class CleaninglogComponent implements OnInit {
  data: any;

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getDetails();
  }

  getDetails() {
    this.service
      .get('common.php?type=get_save_equipment_usage_cleaning_recordLog&Activity_type=Cleaning&depart=' + localStorage.getItem('department'))
      .subscribe((response: any) => {
        this.data = response;
      });
  }
}
