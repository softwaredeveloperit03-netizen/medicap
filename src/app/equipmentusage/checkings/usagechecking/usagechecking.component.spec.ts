import { ComponentFixture, TestBed } from '@angular/core/testing';

import { UsagecheckingComponent } from './usagechecking.component';

describe('UsagecheckingComponent', () => {
  let component: UsagecheckingComponent;
  let fixture: ComponentFixture<UsagecheckingComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ UsagecheckingComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(UsagecheckingComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
