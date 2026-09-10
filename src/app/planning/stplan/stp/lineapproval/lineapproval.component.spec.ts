import { ComponentFixture, TestBed } from '@angular/core/testing';

import { LineapprovalComponent } from './lineapproval.component';

describe('LineapprovalComponent', () => {
  let component: LineapprovalComponent;
  let fixture: ComponentFixture<LineapprovalComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ LineapprovalComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(LineapprovalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
