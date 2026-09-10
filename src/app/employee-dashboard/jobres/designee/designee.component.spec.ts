import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DesigneeComponent } from './designee.component';

describe('DesigneeComponent', () => {
  let component: DesigneeComponent;
  let fixture: ComponentFixture<DesigneeComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DesigneeComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(DesigneeComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
