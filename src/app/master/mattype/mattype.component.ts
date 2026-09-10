import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import * as XLSX from 'xlsx';
import * as FileSaver from 'file-saver';

declare let alertify;

@Component({
  selector: 'app-mattype',
  templateUrl: './mattype.component.html',
  styleUrls: ['./mattype.component.css'],
})
export class MattypeComponent implements OnInit {
  constructor(public service: DataAccessService, private router: Router) {
    this.loggedInDept = localStorage.getItem('department');
    this.plant_id = this.service.getPlantConfigFields('plant_id');
  }

  ngOnInit(): void {
    this.plant_type = this.service.getPlantConfigFields('plant_type');
    this.getgetMaterialtype();
    this.get_rights();
  }

  categoryList;
  plant_id;
  material_types;
  mat_type = '';
  material_subtype = '';
  control_sample_type = '';
  control_reserve_criteria = '';
  retest_type = '';
  restest_months = '';
  plant_type = 'API/Excipients';
  isView = false;

  getgetMaterialtype() {
    this.material_types = [];
    this.service
      .get(
        'master/materialtype.php?type=getrmpmMaterialtype&plant_id=' +
          this.plant_id
      )
      .subscribe((response: any) => {
        this.material_types = response;
      });
  }

  addCategory(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    this.categoryList[Object.keys(this.categoryList).length] = temp;
    data.resetForm();
    const element1 = document.getElementById('category') as HTMLElement;
    element1.focus();
  }

  deleteCategory(index) {
    this.categoryList.splice(index, 1);
  }

  saveMaterialType(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    if (this.isMaterialTypeDuplicate(temp)) {
      alertify.error('Duplicate material type and subtype are not allowed');
      return;
    }
    temp['material_type'] = temp['mat_type'] || temp['material_type'] || '';
    temp['plant_id'] = localStorage.getItem('plant_id');
    temp['categories'] = this.categoryList;
    this.service
      .post(
        'master/materialtype.php?type=saveMaterialtype',
        JSON.stringify(temp)
      )
      .subscribe((response) => {
        if (response['status'] == 'success') {
          alertify.success('Record Inserted Successfully');
          this.mat_type = temp['mat_type'];
          data.resetForm();
          this.getgetMaterialtype();
        } else {
          alertify.error(response['status']);
        }
      });
  }

  delRow(id) {
    this.service
      .get('master/material.php?type=del_type&id=' + id)
      .subscribe((response) => {
        if (response['status'] == 'success') {
          alertify.success('Material Updates Successfully');

          this.getgetMaterialtype();
        } else {
          alertify.error(response['status']);
        }
      });
  }

  // -----------------------------------------12th july------------------------------------------//

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

  get_rights() {
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          localStorage.getItem('emp_id') +
          '&dep_name=' +
          this.loggedInDept
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
  //---------------------------------------------------------------------------------//

  searchQuery: string = '';

  get filteredMaterials(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.material_types; // If no search query, return all materials
    }

    const query = this.searchQuery.toLowerCase().trim();

    return this.material_types.filter((material) => {
      return Object.values(material).some((value) =>
        value?.toString().toLowerCase().includes(query)
      );
    });
  }

  getHighlightedParts(
    text: string,
    query: string
  ): { text: string; match: boolean }[] {
    if (!query || query.trim().length < 2 || !text)
      return [{ text, match: false }];

    const escapedQuery = query.replace(/[-/\\^$*+?.()|[\]{}]/g, '\\$&');
    const regex = new RegExp(`(${escapedQuery})`, 'gi');
    const parts = text.toString().split(regex);

    return parts.map((part) => ({
      text: part,
      match: regex.test(part),
    }));
  }

  private normalizeValue(value: any): string {
    return (value ?? '')
      .toString()
      .trim()
      .replace(/\s+/g, ' ')
      .toLowerCase();
  }

  private isMaterialTypeDuplicate(payload: any): boolean {
    const matType = this.normalizeValue(payload?.mat_type || payload?.material_type);
    const subType = this.normalizeValue(payload?.material_subtype);

    if (!matType || !subType || !Array.isArray(this.material_types)) {
      return false;
    }

    return this.material_types.some((item) => {
      const existingMatType = this.normalizeValue(item?.material_type);
      const existingSubType = this.normalizeValue(item?.material_subtype);
      return existingMatType === matType && existingSubType === subType;
    });
  }

  initCategory(): void {
    this.control_sample_type = '';
    this.control_reserve_criteria = '';
    this.retest_type = '';
    this.restest_months = '';
  }

  exportToExcelMaterialType(): void {
    try {
      const headers = [
        'Sr. No',
        'Material Type',
        'Material Subtype',
        'Control/Reserve Sample',
        'Control/Reserve Sample Criteria',
        'Retest',
        'Retest Months',
        'Entry Date',
        'Entry By',
      ];
      const rows = this.filteredMaterials.map((item, index) => [
        index + 1,
        item.material_type || 'N/A',
        item.material_subtype || 'N/A',
        item.control_sample_type || 'N/A',
        item.control_reserve_criteria || 'N/A',
        item.retest_type || 'N/A',
        item.restest_months || 'N/A',
        item.entry_date || 'N/A',
        item.entry_by || 'N/A',
      ]);
      const data = [headers, ...rows];

      const worksheet: XLSX.WorkSheet = XLSX.utils.aoa_to_sheet(data);

      worksheet['!cols'] = [
        { wch: 10 },
        { wch: 24 },
        { wch: 28 },
        { wch: 24 },
        { wch: 30 },
        { wch: 14 },
        { wch: 14 },
        { wch: 14 },
        { wch: 20 },
      ];
      worksheet['!rows'] = [{ hpx: 28 }, ...rows.map(() => ({ hpx: 22 }))];
      worksheet['!autofilter'] = { ref: 'A1:I1' };

      const headerStyles: any[] = [
        { fgColor: { rgb: '1F4E78' } },
        { fgColor: { rgb: '0B8457' } },
        { fgColor: { rgb: '7A3E9D' } },
        { fgColor: { rgb: 'AD1457' } },
        { fgColor: { rgb: '00838F' } },
        { fgColor: { rgb: 'EF6C00' } },
        { fgColor: { rgb: '6D4C41' } },
        { fgColor: { rgb: '3949AB' } },
        { fgColor: { rgb: '2E7D32' } },
      ];
      ['A1', 'B1', 'C1', 'D1', 'E1', 'F1', 'G1', 'H1', 'I1'].forEach((cellRef, index) => {
        if (!worksheet[cellRef]) {
          return;
        }
        (worksheet[cellRef] as any).s = {
          fill: { patternType: 'solid', ...headerStyles[index] },
          font: { bold: true, color: { rgb: 'FFFFFF' }, sz: 12 },
          alignment: { horizontal: 'center', vertical: 'center' },
          border: {
            top: { style: 'thin', color: { rgb: '000000' } },
            bottom: { style: 'thin', color: { rgb: '000000' } },
            left: { style: 'thin', color: { rgb: '000000' } },
            right: { style: 'thin', color: { rgb: '000000' } },
          },
        };
      });

      const workbook: XLSX.WorkBook = {
        Sheets: { MaterialTypeList: worksheet },
        SheetNames: ['MaterialTypeList'],
      };
      const excelBuffer: any = XLSX.write(workbook, {
        bookType: 'xlsx',
        type: 'array',
      });
      const blob: Blob = new Blob([excelBuffer], {
        type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=UTF-8',
      });
      FileSaver.saveAs(blob, `material_type_list_${new Date().getTime()}.xlsx`);
    } catch (error) {
      console.error('Excel export failed:', error);
      alertify.error('Unable to generate Excel file');
    }
  }
}
