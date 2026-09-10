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

  departments;
  sections;
  section;
  constructor(private service : DataAccessService , private router :Router) {

   }
  ngOnInit(): void {
    this.service.observableDepartment.subscribe(response =>{
      this.departments = response;
    });
  }
  getLocation(index){
    console.log(index)
     this.sections = this.departments[index];
     this.section = this.sections['sections'];
  }
  saveData(data){
      if (!data.valid) {
        alertify.error('All fields are required');
        return;
      }
     console.log(data.value);
      this.service.post('engineering/instrument.php?type=saveInstrument', JSON.stringify(data.value)).subscribe(response => {
        if (response['status'] === 'success') {
          alertify.success('Record Inserted successfully');
          this.router.navigate(['/engineering/instrument']);
          data.resetForm();
        } else {
          alertify.error(response['status']);
        }
      });
    }
}
