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

  results;
  sections;
  selectedResult = [];
  testings: any = [
    { name: "Chemical" },
    { name: "Microbiology" },
  ];

  frequency = 'once in a week';
    flag=false
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getDepartments();
    this.getSection();
  }
  getDepartments() {
    this.service.get('common.php?type=getDepartments').subscribe(response => {
      this.results = response;
    });
  }
  getSection() {
    this.service.get('master/media.php?type=getsection').subscribe(response => {
      this.sections = response;
    });
  }

  getDetails(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedResult = this.results[index];
    }

  }

  // save(data) {

  //   if (!data.valid) {
  //     alertify.error('All fields are required');
  //     return;
  //   }
  //   this.service.post('qc/water.php?type=savePoint', JSON.stringify(data.value)).subscribe(response => {
  //     alertify.success("submitted succesfully");
  //     data.reset();
  //     this.router.navigate(['/microbiology/water/points']);
      
  //   });
  // }
  
  saveData(data){
    if(!data.valid){
      alertify.error('All Fields are Required');
      return;
    }
    this.service.post('qc/water.php?type=savePoint',JSON.stringify(data.value)).subscribe(response =>{
      alertify.success("submitted successfully");
      data.reset();
      this.router.navigate(['/microbiology/water/points']);
    });
  }


  

  onValueChange(newValue: string) {
    const batchNo = /^\d+$/;
    const isValidBatchNo = batchNo.test(newValue);
  
    if (isValidBatchNo) {
      this.flag=false
    } else {

      this.flag=true
    }

}
}
