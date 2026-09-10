import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-stock-transfer',
  templateUrl: './stock-transfer.component.html',
  styleUrls: ['./stock-transfer.component.css']
})
export class StockTransferComponent implements OnInit {

  isView = false;
  isNew = false;
  materials: any;
  searchQuery: any;
  units: any;
  materials_data: any;
  company_unit: any;
  selectedMaterial:any;
  allPlants:any;
  selectedMaterial1 = {
    material_code: '',
    avbl_stock: '',
    batch_qty: '',
    ar_no: '',
    total_qty: '',
    days_to_expiry: '',
    material_name: ''
  };
  
  materials_data1 = [
    { 
      material_code: 'M001', 
      avbl_stock: 100, 
      batch_qty: 50, 
      ar_no: 'AR1234', 
      total_qty: 150, 
      days_to_expiry: 30,
      material_name: 'Material 1'
    },
    { 
      material_code: 'M002', 
      avbl_stock: 200, 
      batch_qty: 75, 
      ar_no: 'AR5678', 
      total_qty: 275, 
      days_to_expiry: 45,
      material_name: 'Material 2'
    },
    // Add more dummy materials as needed
  ];


  constructor(private service: DataAccessService) {
    this.loggedInDept = localStorage.getItem('department');

  }

  async ngOnInit() {
    await this.getAllMaterial();
    await this.getUnits();
    await this.getProducts();
    this.allPlants =  JSON.parse(localStorage.getItem('all_plants'));
    this.get_rights();
  }

  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights;
  loggedInDept;

  get_rights() {this.service.get('hr/employee.php?type=getrights&emp_id=' 
    +localStorage.getItem('emp_id') +'&dep_name=' +this.loggedInDept     
       )
      .subscribe((response) => {
        this.rights = response;
        this.isuser = this.rights[0].isuser;
        this.ischecker = this.rights[0].ischecker;
        this.isapprover = this.rights[0].isapprover;
        this.qms_approver = this.rights[0].qms_approver;
        this.dept_head = this.rights[0].dept_head;
        this.isauditor = this.rights[0].isauditor;
        this.plant_head = this.rights[0].plant_head;
        this.shift_allocator = this.rights[0].shift_allocator;
      });
  }



  getGeneralMaterials(value) {
    return new Promise((res,rej)=>{
      this.service
      .get('common.php?type=getMaterialsByType&material_subtype=' + value)
      .subscribe((response) => {
        this.materials = response;
        // this.selectedMaterial = [];
        for (let i = 0; i < this.materials.length; i++) {
          let material = this.materials[i];
          console.log('material :>> ', material);
          material['gross_total'] = 0;
          material['gst_total'] = 0;
          material['net_total'] = 0;
          material['qty'] = 0;
          material['rate'] = 0;
          this.materials[i] = material;
        }
      });
    })
  }
  getUnits() {
    return new Promise((res, rej) => {
      this.service.observableUnit.subscribe((response) => {
        this.units = response;
        res(this.units);
      });
    });
  }
  products;
  getProducts() {
    return new Promise((res, rej) => {
      this.service.get('common.php?type=getProducts').subscribe((response) => {
        this.products = response;
        res(this.products);
      });
    });
  }
  async onMaterialChange(event:any) {
    console.log(this.selectedMaterial)
    await this.getGeneralMaterials(this.selectedMaterial.material_subtype);
  }
 
  getAllMaterial() {
    this.service
      .get('common.php?type=getallmatdata')
      .subscribe((response: any) => {
        console.log('response',response);
        this.materials_data = response.map(material => {
          return { label: material.material_name, value: material };
        });
      });
  }

  saveUserForm(data) {
    console.log('data :>> ', data);
    const formData = new FormData();
    // formData.append('related_to', data.value.related_to);


    // this.service.post('qa/incident.php?type=saveIncident', formData).subscribe(response => {
    //   const result = JSON.parse(JSON.stringify(response));
    //   if (result.status === 'success') {
    //     data.resetForm();
    //     alert('Saved Successfully');
    //   } else {
    //     alert('An error has occurred, please try again');
    //   }
    //   },
    // (error: Response) => {
    //   if (error.status === 400) {
    //     alert('An error has occurred.');
    //   } else {
    //     alert('An error has occurred, http status:' + error.status);
    //   }
    // });
  }

  onChangeUnitName(event:any) {
    //Table will update based on selection 
   }

  
}
