import { ComponentFixture, TestBed } from '@angular/core/testing';

import { MannitolComponent } from './mannitol.component';

describe('MannitolComponent', () => {
  let component: MannitolComponent;
  let fixture: ComponentFixture<MannitolComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ MannitolComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(MannitolComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
