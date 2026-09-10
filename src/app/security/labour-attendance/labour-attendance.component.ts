import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-labour-attendance',
  templateUrl: './labour-attendance.component.html',
  styleUrls: ['./labour-attendance.component.css']
})
export class LabourAttendanceComponent implements OnInit {
  isMobile = false;
  labours;
  item =[];
  labour_name='';
  category='';
  results;
  todaysLabours;
  isNew= false;
  isPresent =false;
  departments;
  selectAtt =[];
  isAbsent=false;
  constructor(private service: DataAccessService) {
    this.isMobile = this.service.isMobile;
  }

  ngOnInit() {
    this.getActiveLabours();
    this.getTodaysLabors();
  }

  getTodaysLabors() {
    this.service.get('security/labour.php?type=getTodaysLabors').subscribe((response: any) => {
      this.todaysLabours = response;
      this.filterItem()
    });
  }

  getDepartments() {
    this.service.get('common.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }

  getActiveLabours() {
    this.service.get('security/labour.php?type=getActiveLabours').subscribe(response => {
      this.labours = response;
    });
  }

  labourEntry(labour_id) {
    this.service.get('security/labour.php?type=labourEntry&labour_id='+labour_id).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.savedSuccess'));
        this.getTodaysLabors();
        this.isNew = false;
      } else {
        alertify.error('An error occured!');
      }
    });
  }

  exitLabour(id) {
    this.service.get('security/labour.php?type=exitLabour&id='+id).subscribe(response => {
      this.getTodaysLabors();
    });
  }

  present(index,value){
    this.selectAtt = this.todaysLabours[index];
    console.log('button', this.selectAtt);
    this.isPresent =true;
  }

  absent(id,value){
    this.isAbsent =true;
  }

  presentatt(labour_id ,status) {
    this.service.get('security/labour.php?type=labourEntry&status=' + status +'labour_id ='+labour_id).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.savedSuccess'));
        this.getTodaysLabors();
        this.isNew = false;
      } else {
        alertify.error('An error occured!');
      }
    });
  }

  filterItem() {
    this.item = [];
    for (let i = 0; i < this.todaysLabours.length; i++) {
      let material = this.todaysLabours[i];
      if (material['labour_name'].toUpperCase().includes(this.labour_name.toUpperCase())&&material['category'].toUpperCase().includes(this.category.toUpperCase())) {
        this.item[this.item.length] = material;
      }
    }
  }
}
