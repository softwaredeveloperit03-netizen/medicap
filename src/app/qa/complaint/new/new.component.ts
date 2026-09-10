import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  employees;
  complaints;
  selectedCode=[];
  forward = false;
  products;
  isHomepage = true;
  isInvestigationForm = false;
  isComplaintRegister = false;

  departments=[
    {'department_name':'Quality Control','status':''},
    {'department_name':'Production','status':''},
    {'department_name':'Research And Development','status':''},
    {'department_name':'Quality Assurance','status':''},
    {'department_name':'Management','status':''},
    {'department_name':'Regulatory','status':''},
    {'department_name':'Marketing','status':''}
  ];
complaint_type: any;
complaint_sample: any;
    plant_id;
capa: any;

  constructor(private service: DataAccessService, private router:Router) { }

  ngOnInit() {
    this.getMarketComplaints();
    this.getEmployes();
    this.getProducts();
    this.plant_id = this.service.getPlantConfigFields("plant_id")
    
  }

  getProducts(){
    this.service.get('common.php?type=getProducts').subscribe(response=>{
      this.products=response;
    });
  }

  getProdctCodes(index){
    index=index-1;
    if(index!=-1){
      this.selectedCode=this.products[index];
    }
  }

  getEmployes(){
    this.service.get('employee.php?type=getQAPersons').subscribe(response => {
      this.employees = response;
    });
  }

  getMarketComplaints() {
    this.service.get('qaDepartment.php?type=getMarketComplaints').subscribe(response => {
      this.complaints = response;
    });
  }

  savemarketComplaint(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }

    let temp = data.value;

    let test = [];
    for (let i = 0; i < this.departments.length; i++) {
      let department = this.departments[i];
      if (department['status']) {
        test[test.length] = department['department_name'];
      }
    }
    temp['departments'] = test;
    temp['forward'] = this.forward ? 'Yes' : 'No';
    temp['product_code'] = this.selectedCode['product_code'] || '';
    this.service.post('qaDepartment.php?type=createMarketComplaintV2', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == "success") {
        data.resetForm();
        this.forward = false;
        this.selectedCode = [];
        alert('market complaint saved successfully');
        this.getMarketComplaints();
        this.closeForm();
      } else {
        alert('An error occured, please try again');
      }
    });
  }

  checkForm(value) {
    if (value === 'register') {
      this.isHomepage = false;
      this.isComplaintRegister = true;
    } else {
      this.isHomepage = false;
      this.isInvestigationForm = true;
    }
  }

  updateDept(value, i) {
    this.departments[i].status = value;
  }


  closeForm() {
    this.router.navigate(['/qa/complaints']);
  }

}
