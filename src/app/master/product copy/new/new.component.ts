import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { TINYMCE_SCRIPT_SRC } from '@tinymce/tinymce-angular';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  selectedFile1: File;
  selectedFile2: File;
  selectedFile3: File;
  selectedFile4: File;
  selectedFile5: File;
  selectedFile6: File;
  storage_conditions;
  pack_sizes;
  grades;
  clients;
  plants;
  gstList;
  units;
  sale_type = 'Domestic';
  isGrades = false;
  structureFile: File;
  msdsFile: File;
  productLicenseFile: File;
  whoCorpFile: File;
  ceCertificateFile: File;
  structure_file_path;
  product_apperance;
  msds_file_path;
  who_copp_path;
  ce_certificate_path;
  pack_sizes_list = [];
  mrp_list = [];
  sale_price_list = [];
  StuctureFileName: any;
  tempData:any;
  products;
  plant_id:any;
  software_type:any;
  constructor(public service: DataAccessService, private router: Router) {
    this.plant_id = this.service.getPlantConfigFields('plant_id');
    if (this.software_type == null) {
      this.service.getData('https://aurenyxgmp.com/admin/api/clients/client_data_without_token.php?type=get_client_data_by_id&id=' + localStorage.getItem("plant_id")).subscribe(response => {
        localStorage.setItem('client_info', JSON.stringify(response));
        this.plant_id = this.service.getPlantConfigFields('plant_id');
      });

    }
   }

  ngOnInit(): void {
    this.getGrades();
    this.getUnits();
    this.getGst();
    this.getStorageConditions();
    this.getPackSizes();
    this.getProducts();
  }
  getProducts(){
    this.grades =[];
    this.service.get('master/materialtype.php?type=get_fg_api_products').subscribe(response => {
      this.products = response;  
    })
  }
  numberOnly(event): boolean {
    const charCode = (event.which) ? event.which : event.keyCode;
    if (charCode > 31 && (charCode < 48 || charCode > 57)) {
      return false;
    }
    return true;

  }
  getClients(value) {
    if (value == 'Client') {
      this.service.get('common.php?type=getClients').subscribe(response => {
        this.clients = response;
      });
    }
  }
  getUnits() {
    this.service.observableUnit.subscribe(response => {
      this.units = response;
    });
  }
  addGrades(value) {
    if (value == 'Add New') {
      this.isGrades = true;
    } else {
      this.isGrades = false;
    }
  }

  getGrades() {
    this.service.get('master/product.php?type=getGrades').subscribe(response => {
      this.grades = response;
    })
    // this.service.observableGrade.subscribe(response => {
    //   this.grades = response;
    // });
  }
  getGst() {
    this.service.observableGst.subscribe(response => {
      this.gstList = response;
    });

  }
  getStorageConditions() {
    this.service.get('common.php?type=getStorage').subscribe(response => {
      this.storage_conditions = response;
      console.log(this.storage_conditions)
    })
  }
  getPackSizes() {
    this.service.get('common.php?type=getPackSizes').subscribe(response => {
      this.pack_sizes = response;
    })
  }

  saveGrades(data) {
    if (!data.valid) {
      alertify.error('All field are required!');
      return;
    }
    this.service.post('master/product.php?type=saveGrades', JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record Inserted Successfully');
        data.resetForm();
        this.isGrades = false;
        this.getGrades();
      } else {
        alertify.error(response['status']);
      }
    });

  }
  // onFileChanged(event, id, files: FileList) {
  //   console.log(event.target.value);
  //   console.log(files);
  //   const formData = new FormData();
  //   formData.append("file", event.target.files[0]);
  //   this.service.post('master/product.php?type=upload',formData).subscribe(response => {
  //     console.log(response)

  //   })
  //   this.StuctureFileName = event.target.value;
  //   if (event.target.files.length === 1) {
  //     switch (id) {
  //       case 1:
  //         let file = event.target.files[0];
  //         let allImages: Array<string> = ['image/png', 'image/jpg', 'image/jpeg', 'image/gif', 'image/tiff', 'image/bmp'];
  //         if (allImages.indexOf(file.type) === -1) {
  //           alertify.error('File type is not allowed');
  //           console.log(this.structure_file_path);
  //           this.structure_file_path = undefined;

  //           return;
  //         } else {
  //           this.structureFile = event.target.files[0];
  //         }
  //         break
  //       case 2:
  //         this.msdsFile = event.target.files[0];
  //         break
  //       case 3:
  //         this.productLicenseFile = event.target.files[0];
  //         break
  //       case 4:
  //         this.whoCorpFile = event.target.files[0];
  //         break
  //       case 5:
  //         this.ceCertificateFile = event.target.files[0];
  //         break

  //     }

  //   }
  // }
  onFileChanged1(event) {
    if (event.target.files.length >= 1) {
      this.selectedFile1 = event.target.files[0];
    }
  }
  onFileChanged2(event) {
    if (event.target.files.length >= 1) {
      this.selectedFile2 = event.target.files[0];
    }
  }
  onFileChanged3(event) {
    if (event.target.files.length >= 1) {
      this.selectedFile3 = event.target.files[0];
    }
  }
  onFileChanged4(event) {
    if (event.target.files.length >= 1) {
      this.selectedFile4 = event.target.files[0];
    }
  }
  onFileChanged5(event) {
    if (event.target.files.length >= 1) {
      this.selectedFile5 = event.target.files[0];
    }
  }
  onFileChanged6(event) {
    if (event.target.files.length >= 1) {
      this.selectedFile6 = event.target.files[0];
    }
  }
  

  save(data) {
    console.log(data);
    console.log(this.StuctureFileName);

    console.log(JSON.stringify(data.value))
    if (!data.valid) {
      alertify.error('All field are required !');
      return;
    }
    if (this.structureFile != undefined && this.structureFile != null) {
      let allImages: Array<string> = ['image/png', 'image/jpg', 'image/jpeg', 'image/gif', 'image/tiff', 'image/bmp'];
      if (allImages.indexOf(this.structureFile.type) === -1) {
        alertify.error('Image type files only accepted form Structure file ');
        return;
      }
    }
    const uploadData = new FormData();
    if (this.selectedFile1 !== undefined) {
      uploadData.append('structure_file', this.selectedFile1, this.selectedFile1.name);
    }
    if (this.selectedFile2 !== undefined) {
      uploadData.append('msds_file', this.selectedFile2, this.selectedFile2.name);
    }
    if (this.selectedFile3 !== undefined) {
      uploadData.append('prod_license_file', this.selectedFile3, this.selectedFile3.name);
    }
    if (this.selectedFile4 !== undefined) {
      uploadData.append('who_copp_file', this.selectedFile4, this.selectedFile4.name);
    }
    if (this.selectedFile5 !== undefined) {
      uploadData.append('ce_certificate_file', this.selectedFile5, this.selectedFile5.name);
    }
    if (this.selectedFile6 !== undefined) {
      uploadData.append('photo', this.selectedFile6, this.selectedFile6.name);
    }
    let temp = data.value;
    temp['pack_sizes'] = this.pack_sizes_list;
    temp['mrp_list'] = this.mrp_list;
    temp['selling_price_list'] = this.sale_price_list;
    uploadData.append('data', JSON.stringify(temp));
    // uploadData.append()
    console.log(JSON.stringify(temp));
    console.log(JSON.stringify(data.value));
    this.tempData = JSON.stringify(temp);
    console.log(this.tempData);
    this.service.post('master/product.php?type=saveProduct',uploadData).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record Inserted Successfully');
        data.resetForm();
        this.router.navigate(['/master/product'])
      } else {
        alertify.error(response['status']);
      }
    });
  }

  addPackSizes(data) {
    if (!data.valid) {
      alert("All fiels are required");
      return;
    }
    this.pack_sizes_list[this.pack_sizes_list.length] = data.value;
    data.resetForm();
  }
  addMrp(data) {
    if (!data.valid) {
      alert("All fiels are required");
      return;
    }
    this.mrp_list[this.mrp_list.length] = data.value;
    data.resetForm();
  }
  addSellingPrices(data) {
    if (!data.valid) {
      alert("All fiels are required");
      return;
    }
    this.sale_price_list[this.sale_price_list.length] = data.value;
    data.resetForm();
  }

}
