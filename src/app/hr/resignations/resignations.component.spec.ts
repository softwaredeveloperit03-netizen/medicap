import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ResignationsComponent } from './resignations.component';

describe('ResignationComponent', () => {
  let component: ResignationsComponent;
  let fixture: ComponentFixture<ResignationsComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ResignationsComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ResignationsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
