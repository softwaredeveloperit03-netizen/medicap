import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-contract',
  templateUrl: './contract.component.html',
  styleUrls: ['./contract.component.css']
})
export class ContractComponent implements OnInit {

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getGroups();
    this.getVendors();
    this.getUOM();
  }

  // Form fields
  group: any = '';
  subgroup: any = '';
  vendor: any = '';
  vendorMappedMaterial: any = '';
  rate: number = 0;
  rateUOM: any = '';
  deliveryDelayTime: number = 0;
  validFromDate: string = '';
  validToDate: string = '';

  // Dropdown data
  groups: any[] = [];
  subgroups: any[] = [];
  vendors: any[] = [];
  vendorMappedMaterials: any[] = [];
  uomList: any[] = [];

  // Search terms for dropdowns
  searchTermGroup: string = '';
  searchTermSubgroup: string = '';
  searchTermVendor: string = '';
  searchTermMaterial: string = '';

  // Selected items
  selectedGroup: any = {};
  selectedSubgroup: any = {};
  selectedVendor: any = {};
  selectedMaterial: any = {};

  // Get Groups from API
  getGroups() {
    
    this.service.get('marketing/client.php?type=getClientWithGroup').subscribe((response: any) => {
      this.groups = response || [];
    }, error => {
      console.error('Error fetching groups:', error);
      // Fallback: You can set empty array or show error message
      this.groups = [];
    });
  }
  selectedClient1 = [];
  subGroupSeris = [];
  mainGroupclient_code = [];
  mainGroupName:any;
  client_code;
  getSubGroup(index){
    this.selectedClient1=this.groups[index-1]
    this.mainGroupName=this.groups[index-1]['LglNm']
    this.mainGroupclient_code=this.groups[index-1]['client_code']
    this.subGroupSeris = this.groups[index-1]?.clientGroups;
    this.client_code = this.groups[index-1]?.client_code;
    
  }

  // Get filtered subgroups
  get filteredSubgroups() {
    if (!this.searchTermSubgroup) return this.subgroups;
    return this.subgroups.filter(item => {
      const name = (item.subgroup_name || item.name || '').toLowerCase();
      return name.includes(this.searchTermSubgroup.toLowerCase());
    });
  }

  // On Subgroup selection
  onSubgroupSelect(subgroup: any) {
    this.selectedSubgroup = subgroup;
    this.subgroup = subgroup.subgroup_id || subgroup.id || subgroup.subgroup_code;
  }

  // Get Vendors from API
  getVendors() {
    this.service.get('common.php?type=getVendors').subscribe((response: any) => {
      this.vendors = response || [];
    }, error => {
      console.error('Error fetching vendors:', error);
      this.vendors = [];
    });
  }

  // Get Vendor Mapped Materials based on selected Vendor
  onVendorSelect(vendor: any) {
    this.selectedVendor = vendor;
    this.vendor = vendor.vendor_no || vendor.id || vendor.vendor_code;
    this.vendorMappedMaterials = [];
    this.selectedMaterial = {};
    this.vendorMappedMaterial = '';
    
    if (this.vendor) {
      this.service.get('master/material.php?type=get_materials_by_supplier&vendor_no=' + this.vendor).subscribe((response: any) => {
        this.vendorMappedMaterials = response || [];
      }, error => {
        console.error('Error fetching vendor mapped materials:', error);
        this.vendorMappedMaterials = [];
      });
    }
  }

  // Get filtered vendors
  get filteredVendors() {
    if (!this.searchTermVendor) return this.vendors;
    return this.vendors.filter(item => {
      const name = (item.vendor_name || item.name || '').toLowerCase();
      return name.includes(this.searchTermVendor.toLowerCase());
    });
  }

  // Get filtered materials
  get filteredMaterials() {
    if (!this.searchTermMaterial) return this.vendorMappedMaterials;
    return this.vendorMappedMaterials.filter(item => {
      const name = (item.material_name || item.name || '').toLowerCase();
      const code = (item.material_code || item.code || '').toLowerCase();
      const search = this.searchTermMaterial.toLowerCase();
      return name.includes(search) || code.includes(search);
    });
  }

  // On Material selection
  onMaterialSelect(material: any) {
    this.selectedMaterial = material;
    this.vendorMappedMaterial = material.material_code || material.id || material.code;
  }

  // Get UOM List
  getUOM() {
    this.service.get('common.php?type=getUnits_List').subscribe((response: any) => {
      this.uomList = response || [];
    }, error => {
      console.error('Error fetching UOM:', error);
      // Fallback to getUnits if getUnits_List fails
      this.service.get('common.php?type=getUnits').subscribe((response: any) => {
        this.uomList = response || [];
      }, error2 => {
        console.error('Error fetching units:', error2);
        this.uomList = [];
      });
    });
  }
  mainGroup;
  subClient;
  // Save form
  saveForm(form: any) {
    if (!form.valid) {
      alertify.error('All fields are required');
      return;
    }

    if (!this.mainGroup) {
      alertify.error('Please select Group');
      return;
    }

    if (!this.subClient) {
      alertify.error('Please select Subgroup');
      return;
    }

    if (!this.vendor) {
      alertify.error('Please select Vendor');
      return;
    }

    if (!this.vendorMappedMaterial) {
      alertify.error('Please select Vendor Mapped Material');
      return;
    }

    if (this.rate <= 0) {
      alertify.error('Rate must be greater than 0');
      return;
    }

    if (!this.rateUOM) {
      alertify.error('Please select Rate UOM');
      return;
    }

    if (!this.validFromDate) {
      alertify.error('Please select Valid From Date');
      return;
    }

    if (!this.validToDate) {
      alertify.error('Please select Valid To Date');
      return;
    }

    if (new Date(this.validFromDate) > new Date(this.validToDate)) {
      alertify.error('Valid To Date must be after Valid From Date');
      return;
    }

    const formData = {
      main_group: this.mainGroup,
      sub_group: this.subClient,
      vendor: this.vendor,
      material: this.vendorMappedMaterial,
      rate: this.rate,
      rate_uom: this.rateUOM,
      delivery_delay_time: this.deliveryDelayTime,
      valid_from_date: this.validFromDate,
      valid_to_date: this.validToDate
    };

    this.service.post('account/prc.php?type=saveContract', JSON.stringify(formData)).subscribe((response: any) => {
      if (response['status'] === 'success') {
        alertify.success('Contract saved successfully');
        this.resetForm(form);
      } else {
        alertify.error(response['message'] || 'An error occurred, please try again');
      }
    }, error => {
      console.error('Error saving contract:', error);
      alertify.error('An error occurred, please try again');
    });
  }

  // Reset form
  resetForm(form: any) {
    form.resetForm();
    this.group = '';
    this.subgroup = '';
    this.vendor = '';
    this.vendorMappedMaterial = '';
    this.rate = 0;
    this.rateUOM = '';
    this.deliveryDelayTime = 0;
    this.validFromDate = '';
    this.validToDate = '';
    this.selectedGroup = {};
    this.selectedSubgroup = {};
    this.selectedVendor = {};
    this.selectedMaterial = {};
    this.subgroups = [];
    this.vendorMappedMaterials = [];
    this.searchTermGroup = '';
    this.searchTermSubgroup = '';
    this.searchTermVendor = '';
    this.searchTermMaterial = '';
  }
}
