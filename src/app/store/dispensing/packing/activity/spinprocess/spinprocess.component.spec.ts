import { ComponentFixture, TestBed } from '@angular/core/testing';

import { SpinprocessComponent } from './spinprocess.component';

describe('SpinprocessComponent', () => {
  let component: SpinprocessComponent;
  let fixture: ComponentFixture<SpinprocessComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ SpinprocessComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(SpinprocessComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
