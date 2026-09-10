import { ComponentFixture, TestBed } from '@angular/core/testing';

import { VendorChecklistComponent } from './vendor-checklist.component';

describe('VendorChecklistComponent', () => {
  let component: VendorChecklistComponent;
  let fixture: ComponentFixture<VendorChecklistComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ VendorChecklistComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(VendorChecklistComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
