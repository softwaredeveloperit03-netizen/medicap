import { ComponentFixture, TestBed } from '@angular/core/testing';

import { FacordComponent } from './facord.component';

describe('FacordComponent', () => {
  let component: FacordComponent;
  let fixture: ComponentFixture<FacordComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ FacordComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(FacordComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
