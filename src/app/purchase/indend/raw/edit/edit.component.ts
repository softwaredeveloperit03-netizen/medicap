import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-edit',
  templateUrl: './edit.component.html',
  styleUrls: ['./edit.component.css']
})
export class EditComponent implements OnInit {
 

    //----------------------For Pagination---------------------------------//

    currentPage: number = 1;
    pageSize: number = 10; // Default page size
    calculateStartSrNo(): number {
      return (this.currentPage - 1) * 10 ;
    }
    onPageChange(page: number) {
      this.currentPage = page;
      console.log(this.currentPage);
    }
    onPageSizeChange(event: any) {
      this.pageSize = parseInt(event.target.value, 10); // Parse the selected value to an integer
    }
    viewf(){
      this.isView=false;
      //  this.getLogs();
      this.currentPage=1;
      this.pageSize =10;
    }
 

  results;
  selectedResult=[];
  order_type;
  materials=[];
  isView = false;
  searchQuery = '';
  loading = false;



  constructor(private service: DataAccessService) { }

  ngOnInit() {
 
    this.getPendingIndends();
 
  }
 
  getPendingIndends() {
    this.service.get('purchase/indent.php?type=getCheckedIndendsForMerge').subscribe((response: any) => {
      this.results = response;
      this.assignColors();
    });
  }

  view(index) {
    this.selectedResult = this.filteredMaterials[index];
    this.materials = this.selectedResult['materials'];
    this.isView = true;
  }

  materialCodeColors: { [key: string]: string } = {};
  colorList: string[] = [
    '#FFCDD2', '#C8E6C9', '#BBDEFB', '#FFECB3', '#D1C4E9', '#B2DFDB', '#FFF9C4', '#FFCCBC',
    '#F8BBD0', '#DCEDC8', '#B3E5FC', '#FFE0B2', '#E1BEE7', '#B2EBF2', '#FFF9C4', '#FFCCBC',
    '#EF9A9A', '#A5D6A7', '#90CAF9', '#FFE082', '#CE93D8', '#80DEEA', '#FFEB3B', '#FFAB91',
    '#E57373', '#81C784', '#64B5F6', '#FFD54F', '#BA68C8', '#4DD0E1', '#FFEB3B', '#FF7043',
    '#EF5350', '#66BB6A', '#42A5F5', '#FFCA28', '#AB47BC', '#26C6DA', '#FFEB3B', '#FF5722',
    '#F44336', '#4CAF50', '#2196F3', '#FFC107', '#9C27B0', '#00BCD4', '#FFEB3B', '#E64A19',
    '#E53935', '#43A047', '#1E88E5', '#FFB300', '#8E24AA', '#00ACC1', '#FDD835', '#D84315',
    '#D32F2F', '#388E3C', '#1976D2', '#FFA000', '#7B1FA2', '#0097A7', '#FBC02D', '#BF360C',
    '#C62828', '#2E7D32', '#1565C0', '#FF8F00', '#6A1B9A', '#00838F', '#F9A825', '#FF6F00',
    '#B71C1C', '#1B5E20', '#0D47A1', '#FF6F00', '#4A148C', '#006064', '#F57F17', '#E65100',
    '#D50000', '#00C853', '#2962FF', '#FFD600', '#AA00FF', '#00B8D4', '#C6FF00', '#DD2C00',
    '#FF1744', '#00E676', '#2979FF', '#FFC400', '#D500F9', '#00BFA5', '#AEEA00', '#FF3D00',
    '#F50057', '#69F0AE', '#448AFF', '#FFAB00', '#651FFF', '#00E5FF', '#76FF03', '#FF9100',
    '#FF4081', '#B2FF59', '#40C4FF', '#FFD740', '#7C4DFF', '#18FFFF', '#CCFF90', '#FFAB40'
  ];

  assignColors(): void {
    let colorIndex = 0;
    this.filteredMaterials.forEach(result => {
      if (!this.materialCodeColors[result.material_code]) {
        this.materialCodeColors[result.material_code] = this.colorList[colorIndex % this.colorList.length];
        colorIndex++;
      }
    });
  }



  get filteredMaterials(): any[] {
    if (!this.results) return [];
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.results; 
    }
    const query = this.searchQuery.toLowerCase().trim(); 
    return this.results.filter((material: any) => {
      return Object.entries(material).some(([key, value]) => {
        if (key === 'entry_date') {
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          return dateValue instanceof Date && dateValue.toISOString().slice(0, 10).includes(query);
        } else {
          return value && value.toString().toLowerCase().includes(query);
        }
      });
    });
  }
 
  meargeList =[];
  isMerge = false;
  totalQty=0;

  mergeMat() {
    this.totalQty=0;

    this.meargeList =[];
    const selectedItems = this.filteredMaterials.filter((term) => term.selected);
    //this.totalQty = selectedItems.reduce((sum, item) => sum + item.req_qty, 0);
    this.totalQty = selectedItems.reduce((sum, item) => sum + Number(item.req_qty), 0);

  
    if (selectedItems.length === 0) {
      alert('No items selected');
      return;
    }
    
    this.meargeList.push(
      ...selectedItems.map((item) => ({
        id: item.id,
        entry_date: item.entry_date,
        request_no: item.request_no,
        material_type: item.material_type,
        material_code: item.material_code,
        material_name: item.material_name,
        department: item.department,
        entry_by: item.entry_by,
        status: item.status,
        unit: item.unit,
        req_qty: item.req_qty,
        specific_vendor: item.specific_vendor,
        material_subtype: item.material_subtype,
      }))
    );


    this.isMerge = true;


    console.log(this.meargeList);

  }

  isBtn = false;

  SaveMerge(){

    alertify.success('Merging Purchase Requisition');

    this.meargeList[0].req_qty = this.totalQty;
    this.meargeList[0].department = "Store";
    this.final_material.push(this.meargeList[0]);

    if (this.final_material.length > 0) {
      this.isBtn = true;
    }
    
  }








  final_material=[];


  save(){
    if (this.final_material.length == 0) {
      alert('Please Add Material!!!!');
      return;
    }

    let temp ={};
    temp['final_material'] = this.final_material;
    temp['meargeList'] = this.meargeList;
     

    this.service.post('purchase/indent.php?type=saveIndentMerge', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Purchase Requisition merge successful');
        this.final_material = [];
        this.meargeList = [];
        this.isMerge = false;
        this.isBtn = false;
        this.getPendingIndends();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

 

}