import { ComponentFixture, TestBed } from '@angular/core/testing';

import { SpplanapproveComponent } from './spplanapprove.component';

describe('SpplanapproveComponent', () => {
  let component: SpplanapproveComponent;
  let fixture: ComponentFixture<SpplanapproveComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ SpplanapproveComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(SpplanapproveComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
