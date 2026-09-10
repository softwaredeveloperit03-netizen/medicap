import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  constructor(private service:DataAccessService,private router:Router) { }
  
  departments: any[] = [];
  list: { responsibility: string }[] = [];
  responsibilityInput = '';
  ngOnInit() {
    this.getDepartment();
  }
  getDepartment(){
    this.service.get('common.php?type=getDepartments').subscribe((response: any)=>{
      this.departments = Array.isArray(response) ? response : [];
    })
  }

  addResponsibility() {
    const value = String(this.responsibilityInput || '').trim();
    if (!value) {
      alertify.error('Enter responsibility');
      return;
    }
    const exists = this.list.some((x) => String(x.responsibility || '').trim().toLowerCase() === value.toLowerCase());
    if (exists) {
      alertify.error('Responsibility already added');
      return;
    }
    this.list.push({ responsibility: value });
    this.responsibilityInput = '';
  }

  del(index: number) {
    this.list.splice(index, 1);
  }
  addDesignation(data: any) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    if (this.list.length === 0) {
      alertify.error('Add at least one responsibility');
      return;
    }
    const temp = data.value;
    temp['responsibilities'] = this.list;
    this.service.post('hr/designation.php?type=saveDesignation', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        data.resetForm();
        this.list = [];
        this.responsibilityInput = '';
        this.router.navigate(['/master/designation']);
        alertify.success('Position Added Successfully');

      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
