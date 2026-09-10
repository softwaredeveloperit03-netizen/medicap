import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-initiate',
  templateUrl: './initiate.component.html',
  styleUrls: ['./initiate.component.css']
})
export class InitiateComponent implements OnInit {
  products;
  isMaterial=false;
  israw=false;
  ispacking=false;
  isproduct=false;
  deviations=[];
  departments;

    observed = [
      { name: 'Mfg. Process'},
      { name: 'Facility'},
      { name: 'Materials Quality'},
      { name: 'Vendor'},
      { name: 'Equipment, Utility'},
      { name: 'Materials Quantity'},
      { name: 'Specification, Protocol'},
      { name: 'SOP, Formats'},
      { name:'Others'}
    ];
selectedDeviation: any;

  constructor(private service:DataAccessService,private router:Router) { }

  ngOnInit(): void {
    // this.getMaterial();
    this.getDepartments();
  }

  getDepartments(){
    this.departments = [
      { department_name: 'Human Resource', value: false},
      { department_name: 'Quality Control', value: false},
      { department_name: 'Account', value: false},
      { department_name: 'Security', value: false},
      { department_name: 'Purchase', value: false},
      { department_name: 'Quality Assurance', value: false},
      { department_name: 'Engineering', value: false},
      { department_name: 'Store', value: false},
      { department_name: 'Packing', value: false},
      { department_name: 'R & D', value: false},
      { department_name: 'Marketing', value: false},
      { department_name: 'Vendor', value: false},
      { department_name: 'Management', value: false},
      { department_name: 'Planning', value: false},
      { department_name: 'Admin', value: false},
      { department_name: 'IPQA', value: false},
      { department_name: 'Regulatory', value: false},
      { department_name: 'EHS', value: false},
      { department_name: 'Enginering Store', value: false},
      { department_name: 'Production general', value: false},
      { department_name: 'Production HORMONES', value: false},
      { department_name: 'Production', value: false},
    ]

  }

  getDeviationFor(value){
    if(value=='Material'){
      this.isMaterial=true;
      this.isproduct=false;
    }else if(value=='Product'){
      this.getProducts();
      this.isMaterial=false;
      this.isproduct=true;
    }
  }

  getMaterial(value){
    if(value=='Raw Material'){
      this.israw=true;
      this.ispacking=false;
      this.getMaterialsByTypes(value);
    }else if(value=='Packing Material'){
      this.israw=false;
      this.ispacking=true;
      this.getMaterialsByTypes(value);
    }
  }

  getProducts(){
    this.service.get('common.php?type=getProducts').subscribe(response=>{
      this.products=response;
    });
  }
  getMaterialsByTypes(value){
    this.service.get('common.php?type=getMaterialsByType&material_type='+value).subscribe(response=>{
      this.products=response;
    });
  }


  save(data){
    if(!data.valid){
      alertify.error('All feilds are required!');
      return;
    }

    let temp=data.value;
    // temp['']=this.observed;
    this.service.post('qms/deviation.php?type=initiateDeviation',JSON.stringify(data.value)).subscribe(response=>{
      if(response['status']=='success'){
        alertify.success('Data save successfuly');
        data.resetForm();
        this.router.navigate(['/qa/deviation']);
      }else{
        alertify.error('Some error Occured!Please try again');
      }
    });
  }

  updateDept(value, i) {
    this.departments[i].status = value;
  }

}
