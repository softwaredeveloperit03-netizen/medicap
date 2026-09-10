import { ComponentFixture, TestBed } from '@angular/core/testing';

import { IntreciveComponent } from './intrecive.component';

describe('IntreciveComponent', () => {
  let component: IntreciveComponent;
  let fixture: ComponentFixture<IntreciveComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ IntreciveComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(IntreciveComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
