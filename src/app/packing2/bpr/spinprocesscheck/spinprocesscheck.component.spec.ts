import { ComponentFixture, TestBed } from '@angular/core/testing';

import { SpinprocesscheckComponent } from './spinprocesscheck.component';

describe('SpinprocesscheckComponent', () => {
  let component: SpinprocesscheckComponent;
  let fixture: ComponentFixture<SpinprocesscheckComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ SpinprocesscheckComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(SpinprocesscheckComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
