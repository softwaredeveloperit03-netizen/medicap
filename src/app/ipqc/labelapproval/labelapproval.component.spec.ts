import { ComponentFixture, TestBed } from '@angular/core/testing';

import { LabelapprovalComponent } from './labelapproval.component';

describe('LabelapprovalComponent', () => {
  let component: LabelapprovalComponent;
  let fixture: ComponentFixture<LabelapprovalComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ LabelapprovalComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(LabelapprovalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
