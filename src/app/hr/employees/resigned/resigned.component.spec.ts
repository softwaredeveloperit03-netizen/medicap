import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ResignedComponent } from './resigned.component';

describe('ResignedComponent', () => {
  let component: ResignedComponent;
  let fixture: ComponentFixture<ResignedComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ResignedComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ResignedComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
