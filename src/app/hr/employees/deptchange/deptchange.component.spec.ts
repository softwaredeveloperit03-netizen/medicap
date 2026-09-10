import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DeptchangeComponent } from './deptchange.component';

describe('DeptchangeComponent', () => {
  let component: DeptchangeComponent;
  let fixture: ComponentFixture<DeptchangeComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DeptchangeComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(DeptchangeComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
