import { ComponentFixture, TestBed } from '@angular/core/testing';

import { RawapprovalComponent } from './rawapproval.component';

describe('RawapprovalComponent', () => {
  let component: RawapprovalComponent;
  let fixture: ComponentFixture<RawapprovalComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ RawapprovalComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(RawapprovalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
