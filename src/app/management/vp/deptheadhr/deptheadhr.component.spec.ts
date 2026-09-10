import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DeptheadhrComponent } from './deptheadhr.component';

describe('DeptheadhrComponent', () => {
  let component: DeptheadhrComponent;
  let fixture: ComponentFixture<DeptheadhrComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DeptheadhrComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(DeptheadhrComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
